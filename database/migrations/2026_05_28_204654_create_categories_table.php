<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('name_ar')->nullable(); // اسم عربي
            $table->string('slug')->unique();

            // ✅ نوع الفئة
            $table->enum('type', [
                'job_type',      // Full time, Part time...
                'sector',        // Tech, Finance...
                'project_type',  // Startup, Social...
            ]);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
