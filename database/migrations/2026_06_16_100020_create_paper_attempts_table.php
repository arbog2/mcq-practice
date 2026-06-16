<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paper_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_paper_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('question_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('total_score')->default(0);
            $table->string('status', 20)->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'exam_paper_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_attempts');
    }
};
