<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_paper_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_paper_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['exam_paper_id', 'question_id'], 'uq_epq_paper_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_paper_questions');
    }
};
