<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paper_attempts', function (Blueprint $table) {
            $table->string('source', 20)->default('paper')->after('status');
            $table->foreignId('exam_paper_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('paper_attempts', function (Blueprint $table) {
            $table->dropColumn('source');
            $table->foreignId('exam_paper_id')->nullable(false)->change();
        });
    }
};
