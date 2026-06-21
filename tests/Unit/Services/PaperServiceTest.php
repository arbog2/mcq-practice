<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\ExamPaper;
use App\Models\PaperAttempt;
use App\Models\PaperAttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\PaperService;
use App\Services\WrongBookService;
use Tests\TestCase;

class PaperServiceTest extends TestCase
{
    private PaperService $service;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaperService(new WrongBookService);

        $this->category = Category::factory()->create();
    }

    private function createQuestionWithOptions(bool $correct = true): Question
    {
        $question = Question::factory()->create([
            'category_id' => $this->category->id,
            'score' => 2,
        ]);
        $correctOption = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);
        if (! $correct) {
            // flip: make the correct-looking option actually wrong
            $correctOption->update(['is_correct' => false]);
            QuestionOption::factory()->create([
                'question_id' => $question->id,
                'is_correct' => true,
            ]);
        }

        return $question->fresh();
    }

    public function test_start_paper_creates_attempt(): void
    {
        $user = User::factory()->create();
        $question = $this->createQuestionWithOptions();
        $paper = ExamPaper::create([
            'title' => 'Test Paper',
            'description' => 'Desc',
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $attempt = $this->service->startPaper($paper, $user->id);

        $this->assertDatabaseHas('paper_attempts', [
            'id' => $attempt->id,
            'exam_paper_id' => $paper->id,
            'user_id' => $user->id,
            'question_count' => 1,
            'total_score' => 2,
            'status' => PaperAttempt::STATUS_IN_PROGRESS,
        ]);
        $this->assertDatabaseHas('paper_attempt_questions', [
            'paper_attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_start_paper_throws_on_empty(): void
    {
        $user = User::factory()->create();
        $paper = ExamPaper::create([
            'title' => 'Empty Paper',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->service->startPaper($paper, $user->id);
    }

    public function test_start_wrong_book_review_creates_attempt(): void
    {
        $user = User::factory()->create();
        $question = $this->createQuestionWithOptions();

        $attempt = $this->service->startWrongBookReview($user->id, collect([$question->id]));

        $this->assertDatabaseHas('paper_attempts', [
            'id' => $attempt->id,
            'exam_paper_id' => null,
            'user_id' => $user->id,
            'source' => PaperAttempt::SOURCE_WRONG_BOOK,
        ]);
    }

    public function test_start_wrong_book_review_throws_on_empty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service->startWrongBookReview(1, collect());
    }

    public function test_submit_paper_all_correct(): void
    {
        $user = User::factory()->create();
        $question = $this->createQuestionWithOptions(correct: true);
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $user->id]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $attempt = $this->service->startPaper($paper, $user->id);
        $correctOption = $question->options()->where('is_correct', true)->first();

        $result = $this->service->submitPaper($attempt, [
            $question->id => $correctOption->id,
        ]);

        $this->assertSame(1, $result['correct']);
        $this->assertSame(1, $result['total']);
        $this->assertSame(2, $result['score']);
        $this->assertSame(2, $result['total_score']);

        $this->assertDatabaseHas('paper_attempt_answers', [
            'paper_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $correctOption->id,
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('paper_attempts', [
            'id' => $attempt->id,
            'status' => PaperAttempt::STATUS_SUBMITTED,
        ]);
    }

    public function test_submit_paper_some_wrong(): void
    {
        $user = User::factory()->create();
        $q1 = $this->createQuestionWithOptions(correct: true);
        $q2 = $this->createQuestionWithOptions(correct: false);
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $user->id]);
        $paper->questions()->attach($q1->id, ['display_order' => 1]);
        $paper->questions()->attach($q2->id, ['display_order' => 2]);

        $attempt = $this->service->startPaper($paper, $user->id);
        $correctOption = $q1->options()->where('is_correct', true)->first();

        $result = $this->service->submitPaper($attempt, [
            $q1->id => $correctOption->id,
            $q2->id => null,
        ]);

        $this->assertSame(1, $result['correct']);
        $this->assertSame(2, $result['total']);
        $this->assertSame(2, $result['score']);
    }

    public function test_submit_marks_wrong_book(): void
    {
        $user = User::factory()->create();
        $q1 = $this->createQuestionWithOptions(correct: true);
        $q2 = $this->createQuestionWithOptions(correct: false);
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $user->id]);
        $paper->questions()->attach($q1->id, ['display_order' => 1]);
        $paper->questions()->attach($q2->id, ['display_order' => 2]);

        $attempt = $this->service->startPaper($paper, $user->id);
        $correctOption = $q1->options()->where('is_correct', true)->first();
        $wrongOption = $q2->options()->where('is_correct', false)->first();

        $this->service->submitPaper($attempt, [
            $q1->id => $correctOption->id,
            $q2->id => $wrongOption->id,
        ]);

        // q1 should be marked as mastered (if it was in wrong book - it wasn't, so no-op)
        // q2 should appear in wrong book
        $this->assertDatabaseHas('user_wrong_questions', [
            'user_id' => $user->id,
            'question_id' => $q2->id,
            'category_id' => $this->category->id,
            'wrong_count' => 1,
        ]);
    }

    public function test_submit_paper_with_no_answer(): void
    {
        $user = User::factory()->create();
        $question = $this->createQuestionWithOptions();
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $user->id]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $attempt = $this->service->startPaper($paper, $user->id);

        $result = $this->service->submitPaper($attempt, []);

        $this->assertSame(0, $result['correct']);
        $this->assertSame(1, $result['total']);
    }

    public function test_resubmit_updates_existing_answers(): void
    {
        $user = User::factory()->create();
        $question = $this->createQuestionWithOptions();
        $paper = ExamPaper::create(['title' => 'Test', 'is_active' => true, 'created_by' => $user->id]);
        $paper->questions()->attach($question->id, ['display_order' => 1]);

        $attempt = $this->service->startPaper($paper, $user->id);
        $correctOption = $question->options()->where('is_correct', true)->first();
        $wrongOption = $question->options()->where('is_correct', false)->first();

        // First submit with wrong answer
        $this->service->submitPaper($attempt, [$question->id => $wrongOption->id]);

        // Resubmit with correct answer
        $this->service->submitPaper($attempt, [$question->id => $correctOption->id]);

        $answers = PaperAttemptAnswer::where('paper_attempt_id', $attempt->id)->get();
        $this->assertCount(1, $answers);
        $this->assertSame($correctOption->id, $answers[0]->selected_option_id);
        $this->assertTrue((bool) $answers[0]->is_correct);
    }
}
