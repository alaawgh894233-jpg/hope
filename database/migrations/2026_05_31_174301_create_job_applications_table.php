<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_id')
                ->nullable()
                ->constrained('hiring_workflows')
                ->nullOnDelete();

            $table->foreignId('current_stage_id')
                ->nullable()
                ->constrained('workflow_stages')
                ->nullOnDelete();

            $table->text('cover_letter')->nullable();
            $table->string('cv_file')->nullable();
            $table->json('cv_snapshot')->nullable();

            $table->enum('status', [
                'pending',
                'interview',
                'training',
                'accepted',
                'withdrawn',
                'rejected'
            ])->default('pending');

            $table->text('withdraw_reason')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->boolean('can_reapply')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void
    {

    }
};
