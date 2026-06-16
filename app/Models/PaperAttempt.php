<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaperAttempt extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const SOURCE_PAPER = 'paper';

    public const SOURCE_WRONG_BOOK = 'wrong_book';

    protected $fillable = [
        'exam_paper_id',
        'user_id',
        'question_count',
        'correct_count',
        'score',
        'total_score',
        'status',
        'source',
        'started_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ExamPaper::class, 'exam_paper_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PaperAttemptAnswer::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'paper_attempt_questions', 'paper_attempt_id', 'question_id')
            ->withPivot('display_order')
            ->withTimestamps()
            ->orderByPivot('display_order');
    }
}
