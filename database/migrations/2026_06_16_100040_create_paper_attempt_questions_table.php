<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paper_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['paper_attempt_id', 'question_id'], 'uq_paq_attempt_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_attempt_questions');
    }
};
