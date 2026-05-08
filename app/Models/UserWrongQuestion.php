<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWrongQuestion extends Model
{
    protected $fillable = [
        'user_id',
        'question_id',
        'category_id',
        'wrong_count',
        'last_wrong_at',
        'mastered_at',
    ];

    protected function casts(): array
    {
        return [
            'last_wrong_at' => 'datetime',
            'mastered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
