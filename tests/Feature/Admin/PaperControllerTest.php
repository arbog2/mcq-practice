<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\ExamPaper;
use App\Models\Question;
use App\Models\User;
use Tests\TestCase;

class PaperControllerTest extends TestCase
{
    private User $superAdmin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $this->category = Category::factory()->create();
    }

    public function test_index_lists_papers()
    {
        ExamPaper::create(['title' => 'Paper A', 'created_by' => $this->superAdmin->id]);
        ExamPaper::create(['title' => 'Paper B', 'created_by' => $this->superAdmin->id]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.papers.index'))
            ->assertOk();
    }

    public function test_store_creates_paper()
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.papers.store'), [
                'title' => 'New Paper',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('exam_papers', ['title' => 'New Paper']);
    }

    public function test_add_and_remove_question()
    {
        $paper = ExamPaper::create([
            'title' => 'Paper',
            'created_by' => $this->superAdmin->id,
        ]);
        $question = Question::factory()->create(['category_id' => $this->category->id]);

        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.papers.questions.add', $paper), [
                'question_ids' => [$question->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('exam_paper_questions', [
            'exam_paper_id' => $paper->id,
            'question_id' => $question->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('admin.papers.questions.remove', [$paper, $question]))
            ->assertOk();

        $this->assertDatabaseMissing('exam_paper_questions', [
            'exam_paper_id' => $paper->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_stats_shows()
    {
        $paper = ExamPaper::create(['title' => 'Paper', 'created_by' => $this->superAdmin->id]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.papers.stats', $paper))
            ->assertOk();
    }

    public function test_export_stats()
    {
        $paper = ExamPaper::create(['title' => 'Paper', 'created_by' => $this->superAdmin->id]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.papers.stats.export', $paper))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
