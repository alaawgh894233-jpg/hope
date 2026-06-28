<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        // المحادثات - بين المتقدم والشركة بعد القبول
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            // الطلب الوظيفي المرتبط بالمحادثة
            $table->foreignId('job_application_id')
                ->constrained('job_applications')
                ->cascadeOnDelete();
            // المتقدم
            $table->foreignId('applicant_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // الشركة (HR أو صاحب العمل)
            $table->foreignId('company_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // حالة المحادثة
            $table->enum('status', ['active', 'closed', 'archived'])
                ->default('active');
            // آخر رسالة (للعرض السريع)
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            // عدد الرسائل غير المقروءة لكل طرف
            $table->unsignedInteger('applicant_unread_count')->default(0);
            $table->unsignedInteger('company_unread_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            // منع تكرار المحادثة لنفس الطلب
            $table->unique('job_application_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
