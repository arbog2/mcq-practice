<?php

namespace App\Helpers;

use App\Models\Question;
use Illuminate\Support\Collection;

class QuestionHelper
{
    private const LABELS = ['A', 'B', 'C', 'D'];

    public static function shuffledOptions($attempt, Question $question): Collection
    {
        $seed = crc32($attempt->id.'-'.$question->id);

        return $question->options->shuffle($seed)->values();
    }

    public static function labelForOption(Collection $shuffled, ?int $optionId): ?string
    {
        if ($optionId === null) {
            return null;
        }

        foreach ($shuffled as $i => $opt) {
            if ($opt->id === $optionId) {
                return self::LABELS[$i];
            }
        }

        return null;
    }
}
