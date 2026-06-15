<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Models\UserWrongQuestion;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    public function test_category_has_wrong_questions_relationship(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);

        UserWrongQuestion::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category_id' => $category->id,
            'wrong_count' => 1,
            'last_wrong_at' => now(),
        ]);

        $this->assertCount(1, $category->wrongQuestions);
    }

    public function test_with_count_works_on_wrong_questions(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);

        UserWrongQuestion::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category_id' => $category->id,
            'wrong_count' => 1,
            'last_wrong_at' => now(),
        ]);

        $categoryWithCount = Category::withCount('wrongQuestions')->find($category->id);
        $this->assertEquals(1, $categoryWithCount->wrong_questions_count);
    }
}
