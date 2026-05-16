<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $allowedRoles = $this->getAssignableRoles();
        $userId = $this->route('user')->id;

        return [
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in($allowedRoles)],
            'organization_unit_id' => [
                'nullable',
                Rule::exists('organization_units', 'id')->whereNotNull('parent_id'),
            ],
        ];
    }

    private function getAssignableRoles(): array
    {
        $user = $this->user();
        if ($user->isSuperAdmin()) {
            return [User::ROLE_STUDENT, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN];
        }
        return [User::ROLE_STUDENT];
    }
}
