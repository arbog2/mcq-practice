<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Models\UserWrongQuestion;
use Tests\TestCase;

class WrongBookTest extends TestCase
{
    public function test_student_can_view_wrong_book(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);

        UserWrongQuestion::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category_id' => $category->id,
            'wrong_count' => 1,
            'last_wrong_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('student.wrong-book'));

        $response->assertStatus(200);
        $response->assertSee($question->stem);
    }

    public function test_review_form_shows_category_counts(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);

        UserWrongQuestion::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category_id' => $category->id,
            'wrong_count' => 2,
            'last_wrong_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('student.wrong-book.review'));

        $response->assertStatus(200);
        $response->assertSee($category->name);
    }
}
