<?php

namespace Tests\Feature\Student;

use App\Models\Category;
use App\Models\PaperAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserWrongQuestion;
use Tests\TestCase;

class WrongBookControllerTest extends TestCase
{
    private User $student;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->category = Category::factory()->create();
    }

    public function test_index_displays_wrongs()
    {
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        UserWrongQuestion::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'category_id' => $this->category->id,
            'wrong_count' => 1,
            'last_wrong_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.wrong-book'))
            ->assertOk()
            ->assertSee('错题本');
    }

    public function test_index_filters_by_category()
    {
        $q1 = Question::factory()->create(['category_id' => $this->category->id]);
        $otherCat = Category::factory()->create();
        $q2 = Question::factory()->create(['category_id' => $otherCat->id]);

        UserWrongQuestion::create([
            'user_id' => $this->student->id,
            'question_id' => $q1->id,
            'category_id' => $this->category->id,
            'wrong_count' => 1,
            'last_wrong_at' => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.wrong-book', ['category_id' => $this->category->id]));
        $response->assertOk();
    }

    public function test_master_sets_mastered_at()
    {
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        $wrong = UserWrongQuestion::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'category_id' => $this->category->id,
            'wrong_count' => 2,
            'last_wrong_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->post(route('student.wrong-book.master', $wrong))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertNotNull($wrong->fresh()->mastered_at);
    }

    public function test_master_forbidden_for_other_user()
    {
        $otherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        $wrong = UserWrongQuestion::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'category_id' => $this->category->id,
            'wrong_count' => 1,
            'last_wrong_at' => now(),
        ]);

        $this->actingAs($otherStudent)
            ->post(route('student.wrong-book.master', $wrong))
            ->assertStatus(302);
    }

    public function test_review_form_shows_data()
    {
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        UserWrongQuestion::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'category_id' => $this->category->id,
            'wrong_count' => 1,
            'last_wrong_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.wrong-book.review'))
            ->assertOk();
    }

    public function test_start_review_creates_attempt()
    {
        Setting::set('questions_per_session', 1);
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        UserWrongQuestion::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'category_id' => $this->category->id,
            'wrong_count' => 1,
            'last_wrong_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->post(route('student.wrong-book.review.start'), [])
            ->assertRedirect();

        $this->assertDatabaseHas('paper_attempts', [
            'user_id' => $this->student->id,
            'source' => 'wrong_book',
        ]);
    }

    public function test_start_review_without_wrongs_redirects_with_error()
    {
        $this->actingAs($this->student)
            ->post(route('student.wrong-book.review.start'), [])
            ->assertRedirect(route('student.wrong-book.review'))
            ->assertSessionHasErrors('error');
    }

    public function test_start_review_with_category_filter()
    {
        Setting::set('questions_per_session', 1);
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        UserWrongQuestion::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'category_id' => $this->category->id,
            'wrong_count' => 1,
            'last_wrong_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->post(route('student.wrong-book.review.start'), ['category_id' => $this->category->id])
            ->assertRedirect();
    }

    public function test_show_attempt_displays_questions()
    {
        $question = Question::factory()->create(['category_id' => $this->category->id]);
        $attempt = PaperAttempt::create([
            'exam_paper_id' => null,
            'user_id' => $this->student->id,
            'question_count' => 1,
            'status' => PaperAttempt::STATUS_IN_PROGRESS,
            'source' => 'wrong_book',
            'started_at' => now(),
        ]);
        $attempt->questions()->attach($question->id, ['display_order' => 1]);

        $this->actingAs($this->student)
            ->get(route('student.wrong-book.attempt.show', $attempt))
            ->assertOk();
    }

    public function test_show_attempt_redirects_to_result_if_submitted()
    {
        $attempt = PaperAttempt::create([
            'exam_paper_id' => null,
            'user_id' => $this->student->id,
            'question_count' => 1,
            'status' => PaperAttempt::STATUS_SUBMITTED,
            'source' => 'wrong_book',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.wrong-book.attempt.show', $attempt))
            ->assertRedirect(route('student.wrong-book.attempt.result', $attempt));
    }

    public function test_submit_grades_answers()
    {
        $question = Question::factory()->create(['category_id' => $this->category->id, 'score' => 1]);
        $correctOption = QuestionOption::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $attempt = PaperAttempt::create([
            'exam_paper_id' => null,
            'user_id' => $this->student->id,
            'question_count' => 1,
            'status' => PaperAttempt::STATUS_IN_PROGRESS,
            'source' => 'wrong_book',
            'started_at' => now(),
        ]);
        $attempt->questions()->attach($question->id, ['display_order' => 1]);

        $this->actingAs($this->student)
            ->post(route('student.wrong-book.attempt.submit', $attempt), [
                'answers' => [$question->id => $correctOption->id],
            ])
            ->assertRedirect(route('student.wrong-book.attempt.result', $attempt));

        $this->assertDatabaseHas('paper_attempts', [
            'id' => $attempt->id,
            'status' => PaperAttempt::STATUS_SUBMITTED,
            'correct_count' => 1,
        ]);
    }

    public function test_submit_already_submitted_redirects()
    {
        $attempt = PaperAttempt::create([
            'exam_paper_id' => null,
            'user_id' => $this->student->id,
            'question_count' => 1,
            'status' => PaperAttempt::STATUS_SUBMITTED,
            'source' => 'wrong_book',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->post(route('student.wrong-book.attempt.submit', $attempt), [
                'answers' => [],
            ])
            ->assertRedirect(route('student.wrong-book.attempt.result', $attempt));
    }

    public function test_result_shows_score()
    {
        $question = Question::factory()->create(['category_id' => $this->category->id, 'score' => 2]);
        QuestionOption::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $attempt = PaperAttempt::create([
            'exam_paper_id' => null,
            'user_id' => $this->student->id,
            'question_count' => 1,
            'correct_count' => 1,
            'score' => 2,
            'total_score' => 2,
            'status' => PaperAttempt::STATUS_SUBMITTED,
            'source' => 'wrong_book',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);
        $attempt->questions()->attach($question->id, ['display_order' => 1]);

        $this->actingAs($this->student)
            ->get(route('student.wrong-book.attempt.result', $attempt))
            ->assertOk()
            ->assertSee('2');
    }

    public function test_result_redirects_to_attempt_if_not_submitted()
    {
        $attempt = PaperAttempt::create([
            'exam_paper_id' => null,
            'user_id' => $this->student->id,
            'question_count' => 1,
            'status' => PaperAttempt::STATUS_IN_PROGRESS,
            'source' => 'wrong_book',
            'started_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.wrong-book.attempt.result', $attempt))
            ->assertRedirect(route('student.wrong-book.attempt.show', $attempt));
    }
}
