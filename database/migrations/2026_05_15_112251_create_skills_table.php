<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // اسم المهارة
            $table->string('name');

            // نوع المهارة
            $table->enum('type', [
                'technical',
                'tool',
                'language',
                'soft_skill'
            ])->default('technical');

            // مستوى المهارة
            $table->enum('level', [
                'beginner',
                'intermediate',
                'advanced'
            ])->default('beginner');

            // سنوات الخبرة
            $table->integer('years_experience')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
