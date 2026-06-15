<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'category_id',
        'stem',
        'explanation',
        'difficulty',
        'is_active',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'score' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function correctOption(): ?QuestionOption
    {
        return $this->options()->where('is_correct', true)->first();
    }
}
