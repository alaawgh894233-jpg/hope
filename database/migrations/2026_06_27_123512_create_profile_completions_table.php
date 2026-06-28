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
        Schema::create('profile_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->unique();
            // نسبة الاكتمال الكلية (0-100)
            $table->unsignedTinyInteger('percentage')->default(0);
            // تفاصيل الاكتمال لكل قسم
            $table->json('sections')->nullable();
            // مثال:
            // {
            //   "basic_info": { "completed": true, "weight": 20, "points": 20 },
            //   "photo": { "completed": false, "weight": 10, "points": 0 },
            //   "summary": { "completed": false, "weight": 10, "points": 0 },
            //   "experience": { "completed": true, "weight": 20, "points": 20 },
            //   "education": { "completed": true, "weight": 10, "points": 10 },
            //   "skills": { "completed": false, "weight": 15, "points": 0 },
            //   "cv_file": { "completed": false, "weight": 10, "points": 0 },
            //   "certifications": { "completed": false, "weight": 5, "points": 0 }
            // }
            // الأقسام المكتملة فعلاً (لعرض الشارات)
            $table->boolean('has_basic_info')->default(false);
            $table->boolean('has_photo')->default(false);
            $table->boolean('has_summary')->default(false);
            $table->boolean('has_experience')->default(false);
            $table->boolean('has_education')->default(false);
            $table->boolean('has_skills')->default(false);
            $table->boolean('has_cv_file')->default(false);
            $table->boolean('has_certifications')->default(false);
            $table->boolean('has_projects')->default(false);
            // آخر تحديث للحساب
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_completions');
    }
};
