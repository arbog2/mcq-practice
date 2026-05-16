<?php

namespace App\Imports;

use App\Models\OrganizationUnit;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    const CHUNK_SIZE = 100;

    public function collection(Collection $rows): void
    {
        $errors = [];
        $usersData = [];
        $organizationUnits = [];
        $seenUsernames = [];
        $seenEmails = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $r = $row instanceof Collection ? $row->toArray() : (array) $row;

            $username = trim((string) ($r['username'] ?? ''));
            if ($username === '') {
                continue;
            }

            $email = trim((string) ($r['email'] ?? ''));
            $name = trim((string) ($r['name'] ?? ''));
            $passwordPlain = (string) ($r['password'] ?? '');
            $level1 = trim((string) ($r['level_1'] ?? ''));
            $level2 = trim((string) ($r['level_2'] ?? ''));

            if ($name === '') {
                $errors[] = __('第 :row 行：姓名不能为空。', ['row' => $rowNumber]);
                continue;
            }

            if ($passwordPlain === '') {
                $errors[] = __('第 :row 行：密码不能为空。', ['row' => $rowNumber]);
                continue;
            }

            if (($level1 === '') xor ($level2 === '')) {
                $errors[] = __('第 :row 行：一级分类与二级分类需同时填写或同时留空。', ['row' => $rowNumber]);
                continue;
            }

            if (isset($seenUsernames[$username])) {
                $errors[] = __('第 :row 行：用户名 ":username" 在文件中重复。', ['row' => $rowNumber, 'username' => $username]);
                continue;
            }
            $seenUsernames[$username] = true;

            if ($email !== '') {
                if (isset($seenEmails[$email])) {
                    $errors[] = __('第 :row 行：邮箱 ":email" 在文件中重复。', ['row' => $rowNumber, 'email' => $email]);
                    continue;
                }
                $seenEmails[$email] = true;

                $conflict = User::query()
                    ->where('email', $email)
                    ->where('username', '!=', $username)
                    ->exists();

                if ($conflict) {
                    $errors[] = __('第 :row 行：邮箱已被其他用户使用。', ['row' => $rowNumber]);
                    continue;
                }
            }

            $organizationUnitId = null;

            if ($level1 !== '' && $level2 !== '') {
                $parentKey = $level1;
                if (! isset($organizationUnits[$parentKey])) {
                    $parent = OrganizationUnit::query()->firstOrCreate(
                        ['parent_id' => null, 'name' => $level1],
                        ['sort_order' => 0]
                    );
                    $organizationUnits[$parentKey] = $parent;
                } else {
                    $parent = $organizationUnits[$parentKey];
                }

                $childKey = $level1.'|'.$level2;
                if (! isset($organizationUnits[$childKey])) {
                    $child = OrganizationUnit::query()->firstOrCreate(
                        ['parent_id' => $parent->id, 'name' => $level2],
                        ['sort_order' => 0]
                    );
                    $organizationUnits[$childKey] = $child;
                } else {
                    $child = $organizationUnits[$childKey];
                }

                $organizationUnitId = $child->id;
            }

            $usersData[] = [
                'username' => $username,
                'email' => $email ?: null,
                'name' => $name,
                'password' => Hash::make($passwordPlain),
                'role' => User::ROLE_STUDENT,
                'approval_status' => User::APPROVAL_APPROVED,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'organization_unit_id' => $organizationUnitId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        $total = count($usersData);
        $userId = auth()->id();
        Cache::put('import_progress_'.$userId, ['total' => $total, 'current' => 0], 600);

        $processed = 0;
        foreach (array_chunk($usersData, self::CHUNK_SIZE) as $chunk) {
            User::upsert($chunk, ['username']);
            $processed += count($chunk);
            Cache::put('import_progress_'.$userId, ['total' => $total, 'current' => $processed], 600);
        }

        Cache::put('import_progress_'.$userId, ['total' => $total, 'current' => $processed, 'completed' => true], 600);
    }
}
