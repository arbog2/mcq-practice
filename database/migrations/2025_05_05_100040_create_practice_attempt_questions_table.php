<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            // MySQL 索引名最长 64 字符；默认自动生成名称会超长
            $table->unique(['practice_attempt_id', 'question_id'], 'uq_pa_questions_attempt_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_attempt_questions');
    }
};
