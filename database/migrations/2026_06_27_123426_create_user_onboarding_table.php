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
        Schema::create('user_onboarding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->unique(); // مرة واحدة لكل مستخدم
            // نوع المستخدم
            $table->enum('user_type', [
                'user',
                'company',
                'admin'
            ]);
            // الخطوة الحالية
            $table->unsignedTinyInteger('current_step')->default(1);
            // إجمالي الخطوات
            $table->unsignedTinyInteger('total_steps')->default(6);
            // الخطوات المكتملة (JSON)
            $table->json('completed_steps')->nullable();
            // مثال: [1, 2, 3]
            // تفاصيل كل خطوة للمتقدم:
            // 1. معلومات شخصية أساسية (profile)
            // 2. الخبرات (experiences)
            // 3. المهارات (skills)
            // 4. التعليم (education)
            // 5. تحميل السيرة الذاتية (cv_file)
            // 6. تفضيلات الوظيفة (preferences)
            // هل اكتمل الإعداد
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            // هل يتجاهل الإعداد
            $table->boolean('is_skipped')->default(false);
            $table->timestamp('skipped_at')->nullable();
            // تذكير بالإكمال
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_onboarding');
    }
};
