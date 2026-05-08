<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function createUser(array $data, int $approvedBy): User
    {
        return User::create([
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
            'organization_unit_id' => $data['organization_unit_id'] ?? null,
        ]);
    }

    public function updateUser(User $user, array $data): void
    {
        $user->fill([
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'organization_unit_id' => $data['organization_unit_id'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
    }

    public function approveUser(User $user): void
    {
        $user->update([
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);
    }

    public function rejectUser(User $user): void
    {
        $user->update([
            'approval_status' => User::APPROVAL_REJECTED,
            'approved_at' => null,
            'approved_by' => auth()->id(),
        ]);
    }
}
