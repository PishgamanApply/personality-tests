<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assessment_versions', function (Blueprint $table) {
            $table->unsignedSmallInteger('time_limit_minutes')
                ->default(20);
        });
    }

    public function down(): void
    {
        Schema::table('assessment_versions', function (Blueprint $table) {
            $table->dropColumn('time_limit_minutes');
        });
    }
};
