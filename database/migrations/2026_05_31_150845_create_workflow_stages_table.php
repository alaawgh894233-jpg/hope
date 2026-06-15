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

            $table->unsignedInteger('order_index'); // ✅ بس هاد — حذفنا order المكرر

            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_final')->default(false);
            $table->string('final_status')->nullable(); // ✅ 'accepted' or 'rejected'

            $table->timestamps(); // ✅ كانت مدموجة بسطر الـ final_status
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_stages');
    }
};
