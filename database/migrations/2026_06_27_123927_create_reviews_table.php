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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // الطلب الوظيفي المرتبط
            $table->foreignId('job_application_id')
                ->constrained('job_applications')
                ->cascadeOnDelete();
            // المُقيِّم (من يكتب التقييم)
            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // المُقيَّم (من يتلقى التقييم)
            $table->foreignId('reviewee_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // نوع التقييم
            $table->enum('type', [
                'applicant_to_company',   // المتقدم يقيّم الشركة
                'company_to_applicant',   // الشركة تقيّم المتقدم
            ]);
            // ==================
            // تقييمات الشركة (من المتقدم)
            // ==================
            // التقييم العام (1-5)
            $table->unsignedTinyInteger('overall_rating');
            // التقييمات التفصيلية للشركة
            $table->unsignedTinyInteger('work_environment_rating')->nullable();  // بيئة العمل
            $table->unsignedTinyInteger('management_rating')->nullable();         // الإدارة
            $table->unsignedTinyInteger('salary_benefits_rating')->nullable();    // الراتب والمزايا
            $table->unsignedTinyInteger('career_growth_rating')->nullable();      // فرص النمو
            $table->unsignedTinyInteger('work_life_balance_rating')->nullable();  // التوازن بين العمل والحياة
            $table->unsignedTinyInteger('interview_experience_rating')->nullable(); // تجربة المقابلة
            // ==================
            // تقييمات المتقدم (من الشركة)
            // ==================
            $table->unsignedTinyInteger('technical_skills_rating')->nullable();   // المهارات التقنية
            $table->unsignedTinyInteger('communication_rating')->nullable();       // التواصل
            $table->unsignedTinyInteger('professionalism_rating')->nullable();     // الاحترافية
            $table->unsignedTinyInteger('reliability_rating')->nullable();         // الموثوقية
            // ==================
            // النص والتفاصيل
            // ==================
            $table->string('title')->nullable();       // عنوان التقييم
            $table->text('pros')->nullable();          // الإيجابيات
            $table->text('cons')->nullable();          // السلبيات
            $table->text('advice')->nullable();        // نصيحة للإدارة أو للمتقدم
            // هل الوظيفة موصى بها
            $table->boolean('would_recommend')->nullable();
            // هل التقييم مجهول الهوية
            $table->boolean('is_anonymous')->default(false);
            // حالة التقييم
            $table->enum('status', [
                'pending',    // في انتظار المراجعة
                'approved',   // موافق عليه
                'rejected',   // مرفوض (محتوى غير لائق)
                'flagged'     // مُبلَّغ عنه
            ])->default('pending');
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->unsignedInteger('responses_count')->default(0);
            $table->unsignedInteger('flags_count')->default(0);
            // سبب الرفض (إن وجد)
            $table->text('rejection_reason')->nullable();
            // متى أُرسل طلب التقييم
            $table->timestamp('requested_at')->nullable();
            // هل أكمل صاحب التقييم الاستبيان
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            $table->softDeletes();
            // منع التقييم المكرر لنفس الطلب من نفس النوع
            $table->unique(['job_application_id', 'type'], 'unique_review_per_application');

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
