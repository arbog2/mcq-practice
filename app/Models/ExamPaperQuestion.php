<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamPaperQuestion extends Model
{
    protected $fillable = [
        'exam_paper_id',
        'question_id',
        'display_order',
    ];

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ExamPaper::class, 'exam_paper_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
