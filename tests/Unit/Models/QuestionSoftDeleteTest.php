<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\PracticeAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Tests\TestCase;

class QuestionSoftDeleteTest extends TestCase
{
    public function test_question_can_be_soft_deleted(): void
    {
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);

        $question->delete();

        $this->assertSoftDeleted($question);
        $this->assertNotNull(Question::withTrashed()->find($question->id));
    }

    public function test_soft_deleted_question_not_in_default_query(): void
    {
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);

        $question->delete();

        $this->assertNull(Question::find($question->id));
    }

    public function test_practice_attempt_still_shows_soft_deleted_question(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);

        $attempt = PracticeAttempt::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'question_count' => 1,
            'correct_count' => 0,
            'score' => 0,
            'total_score' => 1,
            'status' => PracticeAttempt::STATUS_SUBMITTED,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);
        $attempt->questions()->attach($question->id, ['display_order' => 1]);

        $question->delete();

        $attempt->refresh();
        $this->assertCount(1, $attempt->questions);
        $this->assertEquals($question->id, $attempt->questions->first()->id);
    }
}
