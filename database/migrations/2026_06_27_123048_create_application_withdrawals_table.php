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

        Schema::create('application_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')
                ->constrained('job_applications')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->enum('reason_category', [
                'found_another_job',      // وجد وظيفة أخرى
                'salary_mismatch',        // الراتب لا يناسب
                'location_issue',         // مشكلة في الموقع
                'applied_by_mistake',     // قدّم بالغلط
                'changed_mind',           // غيّر رأيه
                'better_opportunity',     // فرصة أفضل
                'personal_reasons',       // أسباب شخصية
                'other'                   // أخرى
            ])->default('other');
            $table->text('reason_details')->nullable();

            $table->string('previous_status');
            $table->boolean('company_notified')->default(false);
            $table->timestamp('company_notified_at')->nullable();
            $table->timestamps();
        });


}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_withdrawals');
    }
};
