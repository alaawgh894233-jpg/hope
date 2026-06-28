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
        Schema::create('public_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->unique();
            // الرابط المخصص (slug)
            $table->string('slug')->unique();
            // مثال: john-doe-abc123
            // هل البروفايل عام
            $table->boolean('is_public')->default(true);
            // الأقسام المرئية للعموم
            $table->json('visible_sections')->nullable();
            // مثال:
            // {
            //   "contact_info": false,  // إخفاء معلومات الاتصال
            //   "experience": true,
            //   "education": true,
            //   "skills": true,
            //   "projects": true,
            //   "certifications": true,
            //   "reviews": false
            // }
            // إحصائيات المشاهدة
            $table->unsignedInteger('total_views')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            // SEO Meta
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            // Custom theme للبروفايل
            $table->string('theme_color')->nullable()->default('#3B82F6');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_profiles');
    }
};
