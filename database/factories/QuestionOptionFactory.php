<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionOptionFactory extends Factory
{
    protected $model = QuestionOption::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'label' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'content' => $this->faker->sentence(),
            'is_correct' => false,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (array $attributes) => ['is_correct' => true]);
    }
}
