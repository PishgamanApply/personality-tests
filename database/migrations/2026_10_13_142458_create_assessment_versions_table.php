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
        Schema::create('assessment_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assessment_id')
                ->constrained('assessments')
                ->cascadeOnDelete();

            $table->string('version');
            $table->json('scoring_config')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_versions');
    }
};
