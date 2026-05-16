<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STUDENT = 'student';

    public const ROLE_LABELS = [
        self::ROLE_SUPER_ADMIN => '超级管理员',
        self::ROLE_ADMIN => '管理员',
        self::ROLE_STUDENT => '学员',
    ];

    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role',
        'managed_org_unit_ids',
        'approval_status',
        'approved_at',
        'approved_by',
        'organization_unit_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved_at' => 'datetime',
            'managed_org_unit_ids' => 'array',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnit::class);
    }

    public function practiceAttempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }

    public function wrongQuestions(): HasMany
    {
        return $this->hasMany(UserWrongQuestion::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function canPractice(): bool
    {
        if ($this->role !== self::ROLE_STUDENT) {
            return false;
        }

        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function canManageUsers(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->role !== self::ROLE_ADMIN) {
            return false;
        }

        return $this->managed_org_unit_ids === null || is_array($this->managed_org_unit_ids);
    }

    public function canManageUser(User $target): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->canManageUsers()) {
            return false;
        }

        if ($target->role !== self::ROLE_STUDENT) {
            return false;
        }

        $scope = array_map('intval', $this->managed_org_unit_ids ?? []);

        if (empty($scope)) {
            return true;
        }

        return in_array((int) $target->organization_unit_id, $scope, true);
    }

    public function getManagedOrgUnitIds(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }

        return is_array($this->managed_org_unit_ids) ? $this->managed_org_unit_ids : [];
    }
}
