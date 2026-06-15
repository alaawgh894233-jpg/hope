<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ الشركة تعبر عن اهتمام أولاً — بعدين صاحب المشروع يشاركها التفاصيل
        Schema::create('startup_project_interests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('startup_project_id')
                ->constrained('startup_projects')
                ->onDelete('cascade');

            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');

            $table->enum('support_type', ['funding', 'mentoring', 'partnership']);
            $table->text('message')->nullable(); // رسالة الشركة
            $table->decimal('funding_amount', 12, 2)->nullable();

            // ✅ المراحل: interest → approved (صاحب المشروع وافق) → offer_sent → accepted/rejected
            $table->enum('status', [
                'pending',   // الشركة عبرت عن اهتمام
                'approved',  // صاحب المشروع وافق — الشركة تشوف التفاصيل
                'rejected',  // صاحب المشروع رفض
                'accepted',  // اتفقوا
                'withdrawn'  // الشركة سحبت اهتمامها
            ])->default('pending');

            $table->boolean('details_shared')->default(false); // ✅ هل شارك صاحب المشروع التفاصيل؟

            $table->timestamps();

            $table->unique(
                [
                    'startup_project_id',
                    'company_id',
                    'support_type'
                ],
                'startup_interest_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_project_interests');
    }
};
