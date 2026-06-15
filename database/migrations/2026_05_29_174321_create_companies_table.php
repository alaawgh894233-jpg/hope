<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('company_name');

            $table->text('description')->nullable();

            $table->string('website_url')->nullable();

            $table->string('local_address')->nullable();

            $table->string('phone')->nullable();
            // بالـ companies migration
            $table->json('support_offers')->nullable();
// مثال: ["funding", "mentoring", "partnership"]
            $table->string('category')->nullable();
            $table->string('logo')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
