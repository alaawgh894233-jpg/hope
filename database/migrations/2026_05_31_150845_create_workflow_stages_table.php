<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_stages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workflow_id')
                ->constrained('hiring_workflows')
                ->onDelete('cascade');

            $table->string('name');

            $table->enum('type', [
                'applied',
                'screening',
                'interview',
                'training',
                'final_accept',
                'final_reject',
            ]);

            // ✅ ترتيب المرحلة داخل الـ workflow
            $table->unsignedInteger('order_index')->default(0);

            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_final')->default(false);

            // ✅ 'accepted' or 'rejected'
            $table->string('final_status')->nullable();

            $table->timestamps();

            // ✅ index للبحث السريع
            $table->index(['workflow_id', 'order_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_stages');
    }
};
