<?php

namespace App\Services;

use App\Models\ExamPaper;
use App\Models\PaperAttempt;
use App\Models\PaperAttemptAnswer;
use App\Models\Question;
use App\Services\WrongBookService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaperService
{
    const DEFAULT_SCORE = 1;

    public function __construct(
        private readonly WrongBookService $wrongBookService,
    ) {}

    public function startPaper(ExamPaper $paper, int $userId): PaperAttempt
    {
        $questions = $paper->questions()->where('questions.is_active', true)->get(['questions.id', 'score']);

        if ($questions->isEmpty()) {
            throw new \RuntimeException('该试卷暂无可用题目。');
        }

        $totalScore = $questions->sum('score');

        return DB::transaction(function () use ($questions, $totalScore, $paper, $userId) {
            $attempt = PaperAttempt::create([
                'exam_paper_id' => $paper->id,
                'user_id' => $userId,
                'question_count' => $questions->count(),
                'correct_count' => 0,
                'score' => 0,
                'total_score' => $totalScore,
                'status' => PaperAttempt::STATUS_IN_PROGRESS,
                'source' => PaperAttempt::SOURCE_PAPER,
                'started_at' => now(),
            ]);

            $this->attachQuestions($attempt, $questions);

            return $attempt;
        });
    }

    public function startWrongBookReview(int $userId, Collection $questionIds): PaperAttempt
    {
        if ($questionIds->isEmpty()) {
            throw new \RuntimeException('没有可用的错题。');
        }

        $questions = Question::whereIn('id', $questionIds)->get(['id', 'score']);
        $totalScore = $questions->sum('score');

        return DB::transaction(function () use ($questions, $totalScore, $userId) {
            $attempt = PaperAttempt::create([
                'exam_paper_id' => null,
                'user_id' => $userId,
                'question_count' => $questions->count(),
                'correct_count' => 0,
                'score' => 0,
                'total_score' => $totalScore,
                'status' => PaperAttempt::STATUS_IN_PROGRESS,
                'source' => PaperAttempt::SOURCE_WRONG_BOOK,
                'started_at' => now(),
            ]);

            $this->attachQuestions($attempt, $questions);

            return $attempt;
        });
    }

    public function submitPaper(PaperAttempt $attempt, array $answers): array
    {
        try {
            $attempt->load('questions.options');

            return DB::transaction(function () use ($attempt, $answers) {
                $correct = 0;
                $correctScore = 0;

                foreach ($attempt->questions as $question) {
                    $selectedId = $answers[$question->id] ?? null;
                    $selectedOption = $selectedId
                        ? $question->options->firstWhere('id', (int) $selectedId)
                        : null;
                    $isCorrect = $selectedOption && $selectedOption->is_correct;

                    if ($isCorrect) {
                        $correct++;
                        $correctScore += ($question->score ?? self::DEFAULT_SCORE);
                    }

                    PaperAttemptAnswer::updateOrCreate(
                        [
                            'paper_attempt_id' => $attempt->id,
                            'question_id' => $question->id,
                        ],
                        [
                            'selected_option_id' => $selectedOption?->id,
                            'is_correct' => (bool) $isCorrect,
                        ]
                    );

                    if ($isCorrect) {
                        $this->wrongBookService->markCorrect($attempt->user_id, $question->id);
                    } else {
                        $this->wrongBookService->markWrong($attempt->user_id, $question->id, $question->category_id);
                    }
                }

                $attempt->update([
                    'correct_count' => $correct,
                    'score' => $correctScore,
                    'status' => PaperAttempt::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                ]);

                return [
                    'correct' => $correct,
                    'total' => $attempt->question_count,
                    'score' => $correctScore,
                    'total_score' => $attempt->total_score,
                ];
            });
        } catch (\Throwable $e) {
            report($e);
            throw new \RuntimeException('提交失败，请重试。', 0, $e);
        }
    }

    private function attachQuestions(PaperAttempt $attempt, Collection $questions): void
    {
        $pivotData = [];
        foreach ($questions as $order => $question) {
            $pivotData[$question->id] = [
                'display_order' => $order + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $attempt->questions()->attach($pivotData);
    }
}
