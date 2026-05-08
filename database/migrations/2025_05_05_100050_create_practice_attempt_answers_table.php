<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['practice_attempt_id', 'question_id'], 'uq_pa_answers_attempt_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_attempt_answers');
    }
};
