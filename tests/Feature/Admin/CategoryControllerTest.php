<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    private User $superAdmin;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_index_lists_categories(): void
    {
        Category::factory()->create(['name' => 'PHP Basics']);
        Category::factory()->create(['name' => 'MySQL Basics']);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('PHP Basics')
            ->assertSee('MySQL Basics');
    }

    public function test_index_as_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.categories.index'))
            ->assertOk();
    }

    public function test_store_creates_category(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.categories.store'), [
                'name' => 'New Category',
                'sort_order' => 10,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJson(['message' => '分类已创建。']);

        $this->assertDatabaseHas('categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    public function test_store_returns_error_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.categories.store'), ['name' => 'X'])
            ->assertStatus(500);
    }

    public function test_store_validates_name(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.categories.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_store_generates_slug_when_empty(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.categories.store'), [
                'name' => 'Custom Name',
                'slug' => '',
            ])
            ->assertOk();

        $this->assertDatabaseHas('categories', ['slug' => 'custom-name']);
    }

    public function test_update_modifies_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old']);

        $this->actingAs($this->superAdmin)
            ->putJson(route('admin.categories.update', $category), [
                'name' => 'Updated',
                'sort_order' => 5,
            ])
            ->assertOk()
            ->assertJson(['message' => '分类已更新。']);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated', 'sort_order' => 5]);
    }

    public function test_update_returns_error_for_admin(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->putJson(route('admin.categories.update', $category), ['name' => 'X'])
            ->assertStatus(500);
    }

    public function test_destroy_deletes_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('admin.categories.destroy', $category))
            ->assertOk()
            ->assertJson(['message' => '分类已删除。']);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_destroy_returns_error_for_admin(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.categories.destroy', $category))
            ->assertStatus(500);
    }

    public function test_toggle_active(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.categories.toggle-active', $category))
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_active' => false]);
    }

    public function test_toggle_active_twice(): void
    {
        $category = Category::factory()->create(['is_active' => false]);

        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.categories.toggle-active', $category))
            ->assertOk()
            ->assertJsonPath('is_active', true);
    }

    public function test_toggle_active_returns_error_for_admin(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('admin.categories.toggle-active', $category))
            ->assertStatus(500);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
        $this->postJson(route('admin.categories.store'))->assertUnauthorized();
    }
}
