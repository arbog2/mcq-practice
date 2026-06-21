<?php

namespace App\Services;

use App\Models\UserWrongQuestion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

    public function batchMarkCorrect(int $userId, array $questionIds): void
    {
        if (empty($questionIds)) {
            return;
        }

        UserWrongQuestion::where('user_id', $userId)
            ->whereIn('question_id', $questionIds)
            ->whereNull('mastered_at')
            ->update(['mastered_at' => Carbon::now()]);
    }

    public function batchMarkWrong(int $userId, array $wrongItems): void
    {
        if (empty($wrongItems)) {
            return;
        }

        $questionIds = array_column($wrongItems, 'question_id');
        $existing = UserWrongQuestion::where('user_id', $userId)
            ->whereIn('question_id', $questionIds)
            ->get()
            ->keyBy('question_id');

        $now = Carbon::now();
        $newRecords = [];
        $existingIds = [];

        foreach ($wrongItems as $item) {
            $qid = $item['question_id'];
            if (isset($existing[$qid])) {
                $existingIds[] = $qid;
            } else {
                $newRecords[] = [
                    'user_id' => $userId,
                    'question_id' => $qid,
                    'category_id' => $item['category_id'],
                    'wrong_count' => 1,
                    'last_wrong_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($existingIds)) {
            UserWrongQuestion::where('user_id', $userId)
                ->whereIn('question_id', $existingIds)
                ->update([
                    'wrong_count' => DB::raw('wrong_count + 1'),
                    'last_wrong_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        if (! empty($newRecords)) {
            UserWrongQuestion::insert($newRecords);
        }
    }
}
