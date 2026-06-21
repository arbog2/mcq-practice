<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use App\Models\UserWrongQuestion;
use App\Services\WrongBookService;
use Tests\TestCase;

class WrongBookServiceTest extends TestCase
{
    private WrongBookService $service;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WrongBookService;
        $this->category = Category::factory()->create();
    }

    private function makeQuestion(): Question
    {
        return Question::factory()->create(['category_id' => $this->category->id]);
    }

    public function test_mark_correct_sets_mastered_at(): void
    {
        $user = User::factory()->create();
        $question = $this->makeQuestion();
        $record = UserWrongQuestion::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category_id' => $this->category->id,
            'wrong_count' => 2,
            'last_wrong_at' => now()->subDay(),
        ]);

        $this->service->markCorrect($user->id, $question->id);

        $this->assertNotNull($record->fresh()->mastered_at);
    }

    public function test_mark_correct_ignores_not_found(): void
    {
        $user = User::factory()->create();

        $this->service->markCorrect($user->id, 999);

        $this->assertTrue(true);
    }

    public function test_mark_wrong_creates_new_record(): void
    {
        $user = User::factory()->create();
        $question = $this->makeQuestion();

        $this->service->markWrong($user->id, $question->id, $this->category->id);

        $this->assertDatabaseHas('user_wrong_questions', [
            'user_id' => $user->id,
            'question_id' => $question->id,
            'wrong_count' => 1,
        ]);
    }

    public function test_mark_wrong_increments_existing_record(): void
    {
        $user = User::factory()->create();
        $question = $this->makeQuestion();
        UserWrongQuestion::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category_id' => $this->category->id,
            'wrong_count' => 3,
            'last_wrong_at' => now()->subDay(),
        ]);

        $this->service->markWrong($user->id, $question->id, $this->category->id);

        $this->assertDatabaseHas('user_wrong_questions', [
            'user_id' => $user->id,
            'question_id' => $question->id,
            'wrong_count' => 4,
        ]);
    }

    public function test_batch_mark_correct(): void
    {
        $user = User::factory()->create();
        $q1 = $this->makeQuestion();
        $q2 = $this->makeQuestion();
        UserWrongQuestion::create(['user_id' => $user->id, 'question_id' => $q1->id, 'category_id' => $this->category->id, 'wrong_count' => 1, 'last_wrong_at' => now()]);
        UserWrongQuestion::create(['user_id' => $user->id, 'question_id' => $q2->id, 'category_id' => $this->category->id, 'wrong_count' => 1, 'last_wrong_at' => now()]);

        $this->service->batchMarkCorrect($user->id, [$q1->id, $q2->id]);

        $this->assertNotNull(UserWrongQuestion::where('user_id', $user->id)->where('question_id', $q1->id)->first()->mastered_at);
        $this->assertNotNull(UserWrongQuestion::where('user_id', $user->id)->where('question_id', $q2->id)->first()->mastered_at);
    }

    public function test_batch_mark_correct_does_not_affect_already_mastered(): void
    {
        $user = User::factory()->create();
        $q1 = $this->makeQuestion();
        $masteredAt = now()->subDay();
        UserWrongQuestion::create(['user_id' => $user->id, 'question_id' => $q1->id, 'category_id' => $this->category->id, 'wrong_count' => 1, 'last_wrong_at' => now(), 'mastered_at' => $masteredAt]);

        $this->service->batchMarkCorrect($user->id, [$q1->id]);

        $this->assertEquals(
            $masteredAt->timestamp,
            UserWrongQuestion::where('user_id', $user->id)->where('question_id', $q1->id)->first()->mastered_at->timestamp
        );
    }

    public function test_batch_mark_correct_with_empty_ids_does_nothing(): void
    {
        $this->service->batchMarkCorrect(1, []);
        $this->assertTrue(true);
    }

    public function test_batch_mark_wrong_creates_new_records(): void
    {
        $user = User::factory()->create();
        $q1 = $this->makeQuestion();
        $q2 = $this->makeQuestion();

        $this->service->batchMarkWrong($user->id, [
            ['question_id' => $q1->id, 'category_id' => $this->category->id],
            ['question_id' => $q2->id, 'category_id' => $this->category->id],
        ]);

        $this->assertDatabaseHas('user_wrong_questions', ['user_id' => $user->id, 'question_id' => $q1->id, 'wrong_count' => 1, 'category_id' => $this->category->id]);
        $this->assertDatabaseHas('user_wrong_questions', ['user_id' => $user->id, 'question_id' => $q2->id, 'wrong_count' => 1, 'category_id' => $this->category->id]);
    }

    public function test_batch_mark_wrong_increments_existing_and_creates_new(): void
    {
        $user = User::factory()->create();
        $q1 = $this->makeQuestion();
        $q2 = $this->makeQuestion();
        UserWrongQuestion::create(['user_id' => $user->id, 'question_id' => $q1->id, 'category_id' => $this->category->id, 'wrong_count' => 2, 'last_wrong_at' => now()->subDay()]);

        $this->service->batchMarkWrong($user->id, [
            ['question_id' => $q1->id, 'category_id' => $this->category->id],
            ['question_id' => $q2->id, 'category_id' => $this->category->id],
        ]);

        $this->assertDatabaseHas('user_wrong_questions', ['user_id' => $user->id, 'question_id' => $q1->id, 'wrong_count' => 3]);
        $this->assertDatabaseHas('user_wrong_questions', ['user_id' => $user->id, 'question_id' => $q2->id, 'wrong_count' => 1]);
    }

    public function test_batch_mark_wrong_with_empty_items_does_nothing(): void
    {
        $this->service->batchMarkWrong(1, []);
        $this->assertTrue(true);
    }
}
