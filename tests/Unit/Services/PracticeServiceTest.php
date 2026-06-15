<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\PracticeAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Models\UserWrongQuestion;
use App\Services\PracticeService;
use Tests\TestCase;

class PracticeServiceTest extends TestCase
{
    public function test_can_start_practice(): void
    {
        $category = Category::factory()->create();
        Question::factory()->count(5)->create(['category_id' => $category->id]);

        $service = new PracticeService();
        $attempt = $service->startPractice($category, 1);

        $this->assertInstanceOf(PracticeAttempt::class, $attempt);
        $this->assertEquals($category->id, $attempt->category_id);
        $this->assertEquals(PracticeAttempt::STATUS_IN_PROGRESS, $attempt->status);
        $this->assertEquals(5, $attempt->question_count);
    }

    public function test_start_practice_throws_exception_for_empty_category(): void
    {
        $category = Category::factory()->create();

        $service = new PracticeService();

        $this->expectException(\RuntimeException::class);
        $service->startPractice($category, 1);
    }

    public function test_can_submit_practice(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id, 'score' => 2]);
        $correctOption = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        QuestionOption::factory()->count(3)->create(['question_id' => $question->id]);

        $service = new PracticeService();
        $attempt = $service->startPractice($category, $user->id);

        $result = $service->submitPractice($attempt, [
            $question->id => $correctOption->id,
        ]);

        $this->assertEquals(1, $result['correct']);
        $this->assertEquals(2, $result['score']);
        $attempt->refresh();
        $this->assertEquals(PracticeAttempt::STATUS_SUBMITTED, $attempt->status);
    }

    public function test_submit_practice_tracks_wrong_answers(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);
        $correctOption = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        $wrongOption = QuestionOption::factory()->create(['question_id' => $question->id]);

        $service = new PracticeService();
        $attempt = $service->startPractice($category, $user->id);

        $service->submitPractice($attempt, [
            $question->id => $wrongOption->id,
        ]);

        $wrong = UserWrongQuestion::where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();
        $this->assertNotNull($wrong);
        $this->assertEquals(1, $wrong->wrong_count);
    }

    public function test_submit_practice_marks_mastered_on_correct(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);
        $correctOption = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);

        UserWrongQuestion::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category_id' => $category->id,
            'wrong_count' => 2,
            'last_wrong_at' => now(),
        ]);

        $service = new PracticeService();
        $attempt = $service->startPractice($category, $user->id);

        $service->submitPractice($attempt, [
            $question->id => $correctOption->id,
        ]);

        $wrong = UserWrongQuestion::where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();
        $this->assertNotNull($wrong->mastered_at);
    }
}
