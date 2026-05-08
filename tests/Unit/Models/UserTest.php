<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_user_can_check_if_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($superAdmin->isAdmin());
        $this->assertFalse($student->isAdmin());
    }

    public function test_user_can_check_if_super_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($admin->isSuperAdmin());
    }

    public function test_student_can_check_if_can_practice(): void
    {
        $approvedStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
        $pendingStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_PENDING,
        ]);

        $this->assertTrue($approvedStudent->canPractice());
        $this->assertFalse($pendingStudent->canPractice());
    }
}
