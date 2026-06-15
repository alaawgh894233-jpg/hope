<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_analyses', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // analyze | match | tune
            $table->string('type')->default('analyze');

            // FULL SNAPSHOT (original CV)
            $table->json('cv_snapshot')->nullable();

            // FINAL GENERATED CV (IMPORTANT UPGRADE)
            $table->json('cv_final')->nullable();

            // AI + LOGIC SCORES
            $table->integer('ats_score')->nullable();
            $table->integer('match_score')->nullable();
            $table->integer('final_score')->nullable();

            // JOB CONTEXT (IMPORTANT)
            $table->string('job_title')->nullable();
            $table->text('job_description')->nullable();

            // COMPANY TUNING
            $table->string('company')->nullable();

            // INSIGHTS (AI + LOGIC)
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('suggestions')->nullable();

            // AI META
            $table->json('ai_insights')->nullable();
            $table->string('source')->default('hybrid'); // ai | logic | hybrid
            $table->string('model')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_analyses');
    }
};
