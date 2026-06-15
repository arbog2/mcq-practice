<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'stem' => $this->faker->sentence(),
            'explanation' => $this->faker->optional()->paragraph(),
            'difficulty' => $this->faker->numberBetween(1, 5),
            'score' => 1,
            'is_active' => true,
        ];
    }
}
