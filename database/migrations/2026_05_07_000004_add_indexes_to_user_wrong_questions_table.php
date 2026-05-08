<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_wrong_questions', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('question_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_wrong_questions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['question_id']);
            $table->dropIndex(['category_id']);
        });
    }
};
