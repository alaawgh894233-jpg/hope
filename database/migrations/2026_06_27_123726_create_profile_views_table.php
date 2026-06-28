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
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_profile_id')
                ->constrained('public_profiles')
                ->cascadeOnDelete();
            // من شاهد (قد يكون null لو زائر غير مسجل)
            $table->foreignId('viewer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            // IP للزوار غير المسجلين
            $table->string('viewer_ip')->nullable();
            // معلومات إضافية
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamp('viewed_at')->useCurrent();
            $table->index(['public_profile_id', 'viewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_views');
    }
};
