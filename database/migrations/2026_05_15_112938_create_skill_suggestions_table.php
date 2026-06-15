<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_suggestions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // skill المقترحة
            $table->string('name');

            // نوعها
            $table->enum('type', [
                'technical',
                'tool',
                'language',
                'soft_skill'
            ])->default('technical');

            // AI / system / manual
            $table->string('source')->default('ai');

            // سبب الاقتراح
            $table->text('reason')->nullable();

            // مرتبطة بأي هدف وظيفي
            $table->string('job_title')->nullable();

            // confidence من AI
            $table->integer('confidence')->default(50);

            // أولوية للعرض
            $table->integer('priority')->default(50);

            // قرار المستخدم
            $table->enum('status', [
                'pending',
                'accepted',
                'rejected'
            ])->default('pending');

            // إذا قبلها المستخدم وصارت skill
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_suggestions');
    }
};
