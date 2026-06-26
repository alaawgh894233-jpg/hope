<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_project_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('startup_project_id')
                ->constrained('startup_projects')
                ->onDelete('cascade');

            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');

            $table->enum('status', [
                'pending',    // ✅ الدعوة اتبعتت وما ردت الشركة
                'interested', // ✅ الشركة أبدت اهتمام
                'rejected'    // ✅ الشركة رفضت
            ])->default('pending');

            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['startup_project_id', 'company_id'],
                'startup_invitation_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_project_invitations');
    }
};
