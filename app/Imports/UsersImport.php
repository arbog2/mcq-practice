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

        // -- Pre-load existing emails into a map: email => username --
        $fileEmails = [];
        foreach ($rows as $row) {
            $r = $row instanceof Collection ? $row->toArray() : (array) $row;
            $email = trim((string) ($r['email'] ?? ''));
            if ($email !== '') {
                $fileEmails[] = $email;
            }
        }

        $existingEmailMap = [];
        if (! empty($fileEmails)) {
            $existing = User::whereIn('email', $fileEmails)
                ->get(['username', 'email']);
            foreach ($existing as $user) {
                $existingEmailMap[$user->email] = $user->username;
            }
        }

        // -- Pre-load organization units into lookup maps --
        $allUnits = OrganizationUnit::with('parent')->get();

        $parentMap = [];   // name => OrganizationUnit (for root units)
        $childMap = [];    // "parentName|childName" => OrganizationUnit (for leaf units)

        foreach ($allUnits as $unit) {
            if ($unit->parent_id === null) {
                $parentMap[$unit->name] = $unit;
            } else {
                $parentName = $unit->parent?->name;
                if ($parentName !== null) {
                    $childMap[$parentName . '|' . $unit->name] = $unit;
                }
            }
        }

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

                // Check against pre-loaded existing emails (different username)
                if (isset($existingEmailMap[$email]) && $existingEmailMap[$email] !== $username) {
                    $errors[] = __('第 :row 行：邮箱已被其他用户使用。', ['row' => $rowNumber]);
                    continue;
                }
            }

            $organizationUnitId = null;

            if ($level1 !== '' && $level2 !== '') {
                // Look up parent from pre-loaded map, or create
                if (isset($parentMap[$level1])) {
                    $parent = $parentMap[$level1];
                } else {
                    $parent = OrganizationUnit::create([
                        'parent_id' => null,
                        'name' => $level1,
                        'sort_order' => 0,
                    ]);
                    $parentMap[$level1] = $parent;
                }

                // Look up child from pre-loaded map, or create
                $childKey = $level1 . '|' . $level2;
                if (isset($childMap[$childKey])) {
                    $child = $childMap[$childKey];
                } else {
                    $child = OrganizationUnit::create([
                        'parent_id' => $parent->id,
                        'name' => $level2,
                        'sort_order' => 0,
                    ]);
                    $childMap[$childKey] = $child;
                }

                $organizationUnitId = $child->id;
            }

            $actor = auth()->user();
            if ($actor && ! $actor->isSuperAdmin()) {
                $scope = $actor->getManagedOrgUnitIds();
                if (! empty($scope) && $organizationUnitId !== null && ! in_array($organizationUnitId, $scope, true)) {
                    $errors[] = __('第 :row 行：学员分类超出您的管理范围，无权导入。', ['row' => $rowNumber]);
                    continue;
                }
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
