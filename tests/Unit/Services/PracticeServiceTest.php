<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\PracticeAttempt;
use App\Models\Question;
use App\Services\PracticeService;
use Tests\TestCase;

class PracticeServiceTest extends TestCase
{
    public function test_can_start_practice(): void
    {
        $category = Category::factory()->create();
        Question::factory()->count(10)->create(['category_id' => $category->id]);

        $service = new PracticeService();
        $attempt = $service->startPractice($category, 1);

        $this->assertInstanceOf(PracticeAttempt::class, $attempt);
        $this->assertEquals($category->id, $attempt->category_id);
        $this->assertEquals(PracticeAttempt::STATUS_IN_PROGRESS, $attempt->status);
    }

    public function test_start_practice_throws_exception_for_empty_category(): void
    {
        $category = Category::factory()->create();

        $service = new PracticeService();

        $this->expectException(\RuntimeException::class);
        $service->startPractice($category, 1);
    }
}
