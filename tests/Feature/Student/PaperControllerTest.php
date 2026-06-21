<?php

namespace Tests\Feature\Student;

use App\Models\Category;
use App\Models\ExamPaper;
use App\Models\PaperAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Tests\TestCase;

class PaperControllerTest extends TestCase
{
    private User $student;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->category = Category::factory()->create();
    }

    public function test_index_shows_active_papers(): void
    {
        $active = ExamPaper::create(['title' => 'Active Paper', 'is_active' => true, 'created_by' => $this->student->id]);
        ExamPaper::create(['title' => 'Inactive Paper', 'is_active' => false, 'created_by' => $this->student->id]);

        $this->actingAs($this->student)
            ->get(route('student.papers.index'))
            ->assertOk()
            ->assertSee('Active Paper')
            ->assertDontSee('Inactive Paper');
    }

    public function test_index_shows_question_count(): void
    {
        $paper = ExamPaper::create(['title' => 'Paper', 'is_active' => true, 'created_by' => $this->student->id]);
        $q1 = Question::factory()->create(['category_id' => $this->category->id, 'is_active' => true]);
        $q2 = Question::factory()->create(['category_id' => $this->category->id, 'is_active' => true]);
        $paper->questions()->attach([$q1->id => ['display_order' => 1], $q2->id => ['display_order' => 2]]);

        $this->actingAs($this->student)
            ->get(route('student.papers.index'))
            ->assertSee('2');
    }

    public function test_start_creates_attempt_and_redirects(): void
    {
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $this->student->id]);
        $question = Question::factory()->create(['category_id' => $this->category->id, 'is_active' => true]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $this->actingAs($this->student)
            ->post(route('student.papers.start', $paper))
            ->assertRedirect();

        $this->assertDatabaseHas('paper_attempts', [
            'user_id' => $this->student->id,
            'exam_paper_id' => $paper->id,
            'status' => PaperAttempt::STATUS_IN_PROGRESS,
        ]);
    }

    public function test_start_inactive_paper_redirects(): void
    {
        $paper = ExamPaper::create(['title' => 'Inactive', 'is_active' => false, 'created_by' => $this->student->id]);

        $this->actingAs($this->student)
            ->post(route('student.papers.start', $paper))
            ->assertRedirect(route('home'));
    }

    public function test_start_with_empty_paper_redirects_with_error(): void
    {
        $paper = ExamPaper::create(['title' => 'Empty', 'is_active' => true, 'created_by' => $this->student->id]);

        $this->actingAs($this->student)
            ->post(route('student.papers.start', $paper))
            ->assertRedirect(route('student.papers.index'))
            ->assertSessionHasErrors('paper');
    }

    public function test_show_attempt_displays_questions(): void
    {
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $this->student->id]);
        $question = Question::factory()->create(['category_id' => $this->category->id, 'is_active' => true]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $this->actingAs($this->student);
        $attempt = $this->createAttempt($paper);

        $this->actingAs($this->student)
            ->get(route('student.papers.attempts.show', $attempt))
            ->assertOk()
            ->assertSee($question->stem);
    }

    public function test_show_attempt_redirects_to_result_if_submitted(): void
    {
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $this->student->id]);
        $question = Question::factory()->create(['category_id' => $this->category->id, 'score' => 1]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $this->actingAs($this->student);
        $attempt = $this->createAttempt($paper);
        $attempt->update(['status' => PaperAttempt::STATUS_SUBMITTED, 'submitted_at' => now()]);

        $this->actingAs($this->student)
            ->get(route('student.papers.attempts.show', $attempt))
            ->assertRedirect(route('student.papers.attempts.result', $attempt));
    }

    public function test_full_submit_flow_grades_answers(): void
    {
        $paper = ExamPaper::create(['title' => 'Test Paper', 'is_active' => true, 'created_by' => $this->student->id]);
        $question = Question::factory()->create(['category_id' => $this->category->id, 'score' => 3]);
        $correctOption = QuestionOption::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        QuestionOption::factory()->create(['question_id' => $question->id, 'is_correct' => false]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $this->actingAs($this->student);
        $attempt = $this->createAttempt($paper);

        $this->actingAs($this->student)
            ->post(route('student.papers.attempts.submit', $attempt), [
                'answers' => [$question->id => $correctOption->id],
            ])
            ->assertRedirect(route('student.papers.attempts.result', $attempt));

        $this->assertDatabaseHas('paper_attempts', [
            'id' => $attempt->id,
            'correct_count' => 1,
            'score' => 3,
            'status' => PaperAttempt::STATUS_SUBMITTED,
        ]);
    }

    public function test_submit_already_submitted_redirects(): void
    {
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $this->student->id]);
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $this->actingAs($this->student);
        $attempt = $this->createAttempt($paper);
        $attempt->update(['status' => PaperAttempt::STATUS_SUBMITTED, 'submitted_at' => now()]);

        $this->actingAs($this->student)
            ->post(route('student.papers.attempts.submit', $attempt), [
                'answers' => [],
            ])
            ->assertRedirect(route('student.papers.attempts.result', $attempt));
    }

    public function test_result_shows_score(): void
    {
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $this->student->id]);
        $question = Question::factory()->create(['category_id' => $this->category->id, 'score' => 1]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $this->actingAs($this->student);
        $attempt = $this->createAttempt($paper);
        $attempt->update([
            'status' => PaperAttempt::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'correct_count' => 1,
            'score' => 1,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.papers.attempts.result', $attempt))
            ->assertOk()
            ->assertSee('1');
    }

    public function test_result_redirects_to_attempt_if_in_progress(): void
    {
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $this->student->id]);
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $this->actingAs($this->student);
        $attempt = $this->createAttempt($paper);

        $this->actingAs($this->student)
            ->get(route('student.papers.attempts.result', $attempt))
            ->assertRedirect(route('student.papers.attempts.show', $attempt));
    }

    public function test_other_users_cannot_access_attempt(): void
    {
        $otherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $this->student->id]);
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $this->actingAs($this->student);
        $attempt = $this->createAttempt($paper);

        $this->actingAs($otherStudent)
            ->get(route('student.papers.attempts.show', $attempt))
            ->assertRedirect();
    }

    public function test_history_shows_submitted_attempts(): void
    {
        $paper = ExamPaper::create(['title' => 'Done Paper', 'is_active' => true, 'created_by' => $this->student->id]);
        $attempt = PaperAttempt::create([
            'exam_paper_id' => $paper->id,
            'user_id' => $this->student->id,
            'question_count' => 1,
            'correct_count' => 0,
            'score' => 0,
            'total_score' => 1,
            'status' => PaperAttempt::STATUS_SUBMITTED,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        // In-progress should not appear
        PaperAttempt::create([
            'exam_paper_id' => $paper->id,
            'user_id' => $this->student->id,
            'question_count' => 1,
            'status' => PaperAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.papers.history'))
            ->assertOk()
            ->assertSee('Done Paper');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('student.papers.index'))->assertRedirect(route('login'));
        $this->post(route('student.papers.start', 1))->assertRedirect(route('login'));
    }

    public function test_student_with_pending_approval_is_redirected(): void
    {
        $pending = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_PENDING,
        ]);

        $this->actingAs($pending)
            ->get(route('student.papers.index'))
            ->assertRedirect(route('pending.approval'));
    }

    private function createAttempt(ExamPaper $paper): PaperAttempt
    {
        $attempt = PaperAttempt::create([
            'exam_paper_id' => $paper->id,
            'user_id' => $this->student->id,
            'question_count' => $paper->questions()->count(),
            'correct_count' => 0,
            'score' => 0,
            'total_score' => $paper->questions()->sum('score'),
            'status' => PaperAttempt::STATUS_IN_PROGRESS,
            'source' => PaperAttempt::SOURCE_PAPER,
            'started_at' => now(),
        ]);

        foreach ($paper->questions as $i => $question) {
            $attempt->questions()->attach($question->id, ['display_order' => $i + 1]);
        }

        return $attempt->fresh();
    }
}
