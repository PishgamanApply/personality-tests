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
        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assessment_attempt_id')
                ->unique()
                ->constrained('assessment_attempts')
                ->cascadeOnDelete();

            $table->json('scores');
            $table->string('primary_type')->nullable();
            $table->json('report')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
    }
};
