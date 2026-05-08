<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    public function test_question_has_correct_option(): void
    {
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);
        
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);
        $correctOption = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $this->assertNotNull($question->correctOption());
        $this->assertEquals($correctOption->id, $question->correctOption()->id);
    }

    public function test_question_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);

        $this->assertEquals($category->id, $question->category->id);
    }
}
