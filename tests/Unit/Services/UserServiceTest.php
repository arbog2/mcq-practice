<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService;
    }

    public function test_create_user_with_student_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $user = $this->service->createUser([
            'username' => 'teststudent',
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'password' => 'secret123',
            'role' => User::ROLE_STUDENT,
            'organization_unit_id' => null,
            'managed_org_unit_ids' => [],
        ], $admin->id);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'teststudent',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
        $this->assertNull($user->managed_org_unit_ids);
    }

    public function test_create_user_with_admin_role_stores_managed_org_units(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $user = $this->service->createUser([
            'username' => 'testadmin',
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'role' => User::ROLE_ADMIN,
            'organization_unit_id' => null,
            'managed_org_unit_ids' => ['1', '2', '3'],
        ], $superAdmin->id);

        $this->assertSame([1, 2, 3], $user->managed_org_unit_ids);
    }

    public function test_update_user_changes_fields(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->service->updateUser($user, [
            'username' => $user->username,
            'name' => 'New Name',
            'email' => $user->email,
            'role' => User::ROLE_STUDENT,
            'organization_unit_id' => null,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_update_user_with_password(): void
    {
        $user = User::factory()->create();

        $this->service->updateUser($user, [
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_STUDENT,
            'organization_unit_id' => null,
            'password' => 'newpassword',
        ]);

        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }

    public function test_update_user_admin_role_clears_managed_org_units_when_not_admin(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'managed_org_unit_ids' => [1, 2],
        ]);

        $this->service->updateUser($user, [
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_STUDENT,
            'organization_unit_id' => null,
        ]);

        $this->assertNull($user->fresh()->managed_org_unit_ids);
    }

    public function test_approve_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_PENDING,
        ]);

        $this->actingAs($admin);
        $this->service->approveUser($user);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_approve_user_throws_for_already_approved(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);

        $this->expectException(\RuntimeException::class);

        $this->service->approveUser($user);
    }

    public function test_reject_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_PENDING,
        ]);

        $this->actingAs($admin);
        $this->service->rejectUser($user);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'approval_status' => User::APPROVAL_REJECTED,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_reject_user_throws_for_already_rejected(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_REJECTED]);

        $this->expectException(\RuntimeException::class);

        $this->service->rejectUser($user);
    }
}
