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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assessment_version_id')
                ->constrained('assessment_versions')
                ->cascadeOnDelete();

            $table->string('question_key');
            $table->text('prompt');
            $table->string('type')->default('single_choice');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique([
                'assessment_version_id',
                'question_key',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
