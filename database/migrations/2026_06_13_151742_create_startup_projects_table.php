<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->onDelete('set null');

            $table->string('title');
            $table->text('description');
            $table->text('summary');
            $table->string('category')->nullable();
            $table->enum('stage', ['idea', 'in_progress', 'expanding'])->default('idea');
            $table->json('support_types');
            $table->decimal('funding_goal', 12, 2)->nullable();
            $table->string('location')->nullable();
            $table->string('website_url')->nullable();
            $table->string('image')->nullable();

            $table->enum('status', [
                'draft',       // ✅ الفكرة اتكتبت بس ما اتبعت دعوات
                'inviting',    // ✅ اتبعتت دعوات وننتظر ردود
                'in_progress', // ✅ شركة اتوافق عليها
                'closed'       // ✅ المشروع انتهى
            ])->default('draft');

            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('offers_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_projects');
    }
};
