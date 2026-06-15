<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_attempts', function (Blueprint $table) {
            $table->unsignedInteger('total_score')->default(0)->after('score');
        });

        DB::statement('ALTER TABLE practice_attempts MODIFY score INT UNSIGNED DEFAULT 0');
    }

    public function down(): void
    {
        Schema::table('practice_attempts', function (Blueprint $table) {
            $table->dropColumn('total_score');
        });
    }
};
