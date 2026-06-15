<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_post_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')
                ->constrained('job_posts')
                ->onDelete('cascade');
            $table->foreignId('category_id')
                ->constrained('categories')
                ->onDelete('cascade');

            // ✅ منع التكرار
            $table->unique(['job_post_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_post_category');
    }
};
