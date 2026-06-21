<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    public function test_index_shows_students()
    {
        User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_create_shows_form()
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.users.create'))
            ->assertOk();
    }

    public function test_store_validates_required_fields()
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.users.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'name', 'password', 'role']);
    }

    public function test_approve_user()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_PENDING,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.users.approve', $user))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
    }

    public function test_reject_user()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_PENDING,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.users.reject', $user))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'approval_status' => User::APPROVAL_REJECTED,
        ]);
    }

    public function test_import_form_shows()
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.users.import'))
            ->assertOk();
    }

    public function test_import_template_downloads()
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.users.import.template'))
            ->assertOk();
    }

    public function test_import_progress()
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.import.progress'))
            ->assertJson(['total' => 0, 'current' => 0]);
    }
}
