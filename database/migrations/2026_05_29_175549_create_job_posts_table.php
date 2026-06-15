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
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description');

            $table->string('location')->nullable();
            $table->boolean('is_remote')->default(false);

            $table->string('salary_range')->nullable();

            $table->enum('type', [
                'full_time',
                'part_time',
                'contract',
                'internship',
                'freelance'
            ])->default('full_time');

            $table->enum('status', [
                'draft',
                'published',
                'closed'
            ])->default('published');

            $table->json('skills')->nullable();

            $table->integer('views')->default(0);
            $table->integer('applications_count')->default(0);
            $table->json('tags')->nullable();
            $table->boolean('is_featured')->default(false);

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};
