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
        Schema::create('job_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // اسم التنبيه
            $table->string('name');
            // معايير البحث المحفوظة (JSON)
            $table->json('criteria');
            // مثال:
            // {
            //   "keywords": ["Laravel", "PHP"],
            //   "location": "Dubai",
            //   "job_type": ["full_time"],
            //   "experience_level": ["mid", "senior"],
            //   "salary_min": 5000,
            //   "salary_max": 15000,
            //   "remote": true,
            //   "categories": [1, 3, 5]
            // }
            // تكرار الإشعار
            $table->enum('frequency', [
                'instantly',  // فوري
                'daily',      // يومي
                'weekly',     // أسبوعي
            ])->default('daily');
            // قنوات الإشعار
            $table->boolean('notify_email')->default(true);
            $table->boolean('notify_push')->default(true);

            // هل التنبيه نشط
            $table->boolean('is_active')->default(true);
            // آخر مرة أُرسل فيها التنبيه
            $table->timestamp('last_sent_at')->nullable();
            // عدد الوظائف المُرسَلة إجمالاً
            $table->unsignedInteger('total_sent')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_alerts');
    }
};
