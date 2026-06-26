<?php
// database/migrations/xxxx_create_cv_files_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_name');        // اسم الملف الأصلي
            $table->string('stored_path');           // المسار الفعلي على السيرفر
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime_type')->nullable();
            $table->json('extracted_cv')->nullable(); // البيانات المستخرجة
            $table->json('improved_cv')->nullable();  // CV بعد التحسين
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_files');
    }
};
