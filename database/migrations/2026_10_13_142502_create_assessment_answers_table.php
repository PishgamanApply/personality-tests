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
        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assessment_attempt_id')
                ->constrained('assessment_attempts')
                ->cascadeOnDelete();

            $table->foreignId('question_id')
                ->constrained('questions')
                ->restrictOnDelete();

            $table->foreignId('selected_option_id')
                ->nullable()
                ->constrained('question_options')
                ->nullOnDelete();

            $table->json('answer_value')->nullable();
            $table->timestamps();

            $table->unique([
                'assessment_attempt_id',
                'question_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
    }
};
