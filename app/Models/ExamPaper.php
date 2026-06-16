<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamPaper extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'is_active',
        'total_score',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'total_score' => 'integer',
        ];
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_paper_questions')
            ->withPivot('display_order')
            ->withTimestamps()
            ->orderByPivot('display_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PaperAttempt::class, 'exam_paper_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
