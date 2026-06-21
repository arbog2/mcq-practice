<?php

namespace App\Services;

use App\Models\UserWrongQuestion;
use Illuminate\Support\Carbon;

class WrongBookService
{
    public function markCorrect(int $userId, int $questionId): void
    {
        UserWrongQuestion::where('user_id', $userId)
            ->where('question_id', $questionId)
            ->whereNull('mastered_at')
            ->update(['mastered_at' => Carbon::now()]);
    }

    public function markWrong(int $userId, int $questionId, int $categoryId): void
    {
        $record = UserWrongQuestion::firstOrNew([
            'user_id' => $userId,
            'question_id' => $questionId,
        ]);
        $record->category_id = $categoryId;
        $record->wrong_count = ($record->wrong_count ?? 0) + 1;
        $record->last_wrong_at = Carbon::now();
        $record->save();
    }
}
