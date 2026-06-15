<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_application_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('interviewer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('scheduled_at');

            $table->enum('type', ['online', 'offline', 'phone'])->default('online');

            $table->enum('status', [
                'scheduled',
                'passed',
                'failed',
                'cancelled'
            ])->default('scheduled');

            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->text('notes')->nullable();
            $table->text('feedback')->nullable();
            $table->integer('score')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
