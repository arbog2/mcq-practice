<?php

namespace App\Services;

use App\Models\Category;
use App\Models\PracticeAttempt;
use App\Models\PracticeAttemptAnswer;
use App\Models\Question;
use App\Models\Setting;
use App\Models\UserWrongQuestion;
use Illuminate\Support\Facades\DB;

class PracticeService
{
    public function startPractice(Category $category, int $userId): PracticeAttempt
    {
        $perSession = max(1, (int) Setting::get('questions_per_session', config('practice.questions_per_session')));

        $questionIds = Question::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit($perSession)
            ->pluck('id');

        if ($questionIds->isEmpty()) {
            throw new \RuntimeException('该分类下暂无可用题目。');
        }

        return DB::transaction(function () use ($questionIds, $category, $userId) {
            $attempt = PracticeAttempt::create([
                'user_id' => $userId,
                'category_id' => $category->id,
                'question_count' => $questionIds->count(),
                'correct_count' => 0,
                'score' => 0,
                'status' => PracticeAttempt::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            $order = 1;
            foreach ($questionIds as $questionId) {
                $attempt->questions()->attach($questionId, [
                    'display_order' => $order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $order++;
            }

            return $attempt;
        });
    }

    public function startPracticeWithQuestions(int $categoryId, int $userId, $questionIds): PracticeAttempt
    {
        if ($questionIds->isEmpty()) {
            throw new \RuntimeException('没有可用的题目。');
        }

        return DB::transaction(function () use ($questionIds, $categoryId, $userId) {
            $attempt = PracticeAttempt::create([
                'user_id' => $userId,
                'category_id' => $categoryId,
                'question_count' => $questionIds->count(),
                'correct_count' => 0,
                'score' => 0,
                'status' => PracticeAttempt::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            $order = 1;
            foreach ($questionIds as $questionId) {
                $attempt->questions()->attach($questionId, [
                    'display_order' => $order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $order++;
            }

            return $attempt;
        });
    }

    public function submitPractice(PracticeAttempt $attempt, array $answers): array
    {
        try {
            return DB::transaction(function () use ($attempt, $answers) {
                $correct = 0;
                $attempt->load('questions.options');

                foreach ($attempt->questions as $question) {
                    $selectedId = $answers[$question->id] ?? null;
                    $selectedOption = $selectedId
                        ? $question->options->firstWhere('id', (int) $selectedId)
                        : null;
                    $isCorrect = $selectedOption && $selectedOption->is_correct;

                    if ($isCorrect) {
                        $correct++;
                    }

                    PracticeAttemptAnswer::updateOrCreate(
                        [
                            'practice_attempt_id' => $attempt->id,
                            'question_id' => $question->id,
                        ],
                        [
                            'selected_option_id' => $selectedOption?->id,
                            'is_correct' => (bool) $isCorrect,
                        ]
                    );

                    if ($isCorrect) {
                        UserWrongQuestion::where('user_id', $attempt->user_id)
                            ->where('question_id', $question->id)
                            ->whereNull('mastered_at')
                            ->update(['mastered_at' => now()]);
                    } else {
                        $record = UserWrongQuestion::firstOrNew([
                            'user_id' => $attempt->user_id,
                            'question_id' => $question->id,
                        ]);
                        $record->category_id = $question->category_id;
                        $record->wrong_count = ($record->wrong_count ?? 0) + 1;
                        $record->last_wrong_at = now();
                        $record->save();
                    }
                }

                $total = $attempt->question_count ?: $attempt->questions()->count();
                $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;

                $attempt->update([
                    'correct_count' => $correct,
                    'score' => $score,
                    'status' => PracticeAttempt::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                ]);

                return ['correct' => $correct, 'total' => $total, 'score' => $score];
            });
        } catch (\Throwable $e) {
            report($e);
            throw new \RuntimeException('提交失败，请重试。', 0, $e);
        }
    }
}
