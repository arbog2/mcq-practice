<?php

namespace App\Imports;

use App\Models\OrganizationUnit;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        $errors = [];
        $usersToInsert = [];
        $organizationUnits = [];

        DB::transaction(function () use ($rows, &$errors, &$usersToInsert, &$organizationUnits) {
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

                if ($email === '' || $name === '') {
                    $errors[] = __('第 :row 行：邮箱与姓名不能为空。', ['row' => $rowNumber]);
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

                $conflict = User::query()
                    ->where('email', $email)
                    ->where('username', '!=', $username)
                    ->exists();

                if ($conflict) {
                    $errors[] = __('第 :row 行：邮箱已被其他用户使用。', ['row' => $rowNumber]);
                    continue;
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

                    $childKey = $level1 . '|' . $level2;
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

                $usersToInsert[] = [
                    'username' => $username,
                    'email' => $email,
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

            if (! empty($usersToInsert)) {
                foreach ($usersToInsert as $userData) {
                    User::query()->updateOrCreate(
                        ['username' => $userData['username']],
                        $userData
                    );
                }
            }
        });

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'file' => $errors,
            ]);
        }
    }
}
