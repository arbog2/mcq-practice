<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Tests\TestCase;

class QuestionControllerTest extends TestCase
{
    private User $superAdmin;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->category = Category::factory()->create();
    }

    public function test_index_as_super_admin(): void
    {
        Question::factory()->count(3)->create(['category_id' => $this->category->id]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.questions.index'))
            ->assertOk();
    }

    public function test_index_as_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.questions.index'))
            ->assertOk();
    }

    public function test_index_filters_by_category(): void
    {
        $q1 = Question::factory()->create(['category_id' => $this->category->id]);
        $other = Category::factory()->create();
        $q2 = Question::factory()->create(['category_id' => $other->id]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.questions.index', ['category_id' => $this->category->id]))
            ->assertSee($q1->stem)
            ->assertDontSee($q2->stem);
    }

    public function test_create_redirects_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.questions.create'))
            ->assertStatus(302);
    }

    public function test_store_creates_question_with_options(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'stem' => 'What is 2+2?',
            'explanation' => 'Basic math',
            'difficulty' => 1,
            'score' => 2,
            'is_active' => true,
            'option0' => '3',
            'option1' => '4',
            'option2' => '5',
            'option3' => '6',
            'correct_index' => 1,
        ];

        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.questions.store'), $payload)
            ->assertOk()
            ->assertJson(['message' => '题目已创建。']);

        $this->assertDatabaseHas('questions', [
            'stem' => 'What is 2+2?',
            'category_id' => $this->category->id,
            'score' => 2,
        ]);

        $question = Question::where('stem', 'What is 2+2?')->first();
        $this->assertCount(4, $question->options);
        $this->assertTrue($question->options->where('label', 'B')->first()->is_correct);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.questions.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'stem', 'option0', 'option1', 'option2', 'option3', 'correct_index']);
    }

    public function test_store_returns_error_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.questions.store'), [
                'category_id' => $this->category->id,
                'stem' => 'test',
                'option0' => 'a', 'option1' => 'b', 'option2' => 'c', 'option3' => 'd',
                'correct_index' => 0,
            ])
            ->assertStatus(500);
    }

    public function test_edit_shows_form(): void
    {
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.questions.edit', $question))
            ->assertOk()
            ->assertSee($question->stem);
    }

    public function test_edit_redirects_for_admin(): void
    {
        $question = Question::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.questions.edit', $question))
            ->assertStatus(302);
    }

    public function test_update_modifies_question(): void
    {
        $question = Question::factory()->create(['category_id' => $this->category->id, 'stem' => 'Old stem']);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);

        $this->actingAs($this->superAdmin)
            ->putJson(route('admin.questions.update', $question), [
                'category_id' => $this->category->id,
                'stem' => 'New stem',
                'explanation' => 'Updated',
                'option0' => 'a', 'option1' => 'b', 'option2' => 'c', 'option3' => 'd',
                'correct_index' => 2,
            ])
            ->assertOk()
            ->assertJson(['message' => '题目已更新。']);

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'stem' => 'New stem']);
    }

    public function test_destroy_soft_deletes(): void
    {
        $question = Question::factory()->create();

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('admin.questions.destroy', $question))
            ->assertOk()
            ->assertJson(['message' => '题目已删除。']);

        $this->assertSoftDeleted($question);
    }

    public function test_destroy_returns_error_for_admin(): void
    {
        $question = Question::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.questions.destroy', $question))
            ->assertStatus(500);
    }

    public function test_batch_move_category(): void
    {
        $questions = Question::factory()->count(3)->create(['category_id' => $this->category->id]);
        $newCategory = Category::factory()->create();

        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.questions.batch-move'), [
                'ids' => $questions->pluck('id')->toArray(),
                'category_id' => $newCategory->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('questions', ['id' => $questions[0]->id, 'category_id' => $newCategory->id]);
    }

    public function test_batch_destroy(): void
    {
        $questions = Question::factory()->count(2)->create();

        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.questions.batch-destroy'), [
                'ids' => $questions->pluck('id')->toArray(),
            ])
            ->assertOk();

        foreach ($questions as $q) {
            $this->assertSoftDeleted($q);
        }
    }

    public function test_batch_score(): void
    {
        $questions = Question::factory()->count(2)->create(['score' => 1]);

        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.questions.batch-score'), [
                'ids' => $questions->pluck('id')->toArray(),
                'score' => 5,
            ])
            ->assertOk();

        $this->assertDatabaseHas('questions', ['id' => $questions[0]->id, 'score' => 5]);
    }

    public function test_move_category(): void
    {
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        $newCategory = Category::factory()->create();

        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.questions.move', $question), [
                'category_id' => $newCategory->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'category_id' => $newCategory->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.questions.index'))->assertRedirect(route('login'));
        $this->postJson(route('admin.questions.store'))->assertUnauthorized();
    }
}
