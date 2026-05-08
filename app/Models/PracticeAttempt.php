<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeAttempt extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    protected $fillable = [
        'user_id',
        'category_id',
        'question_count',
        'correct_count',
        'score',
        'status',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PracticeAttemptAnswer::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'practice_attempt_questions', 'practice_attempt_id', 'question_id')
            ->withPivot('display_order')
            ->withTimestamps()
            ->orderByPivot('display_order');
    }
}
