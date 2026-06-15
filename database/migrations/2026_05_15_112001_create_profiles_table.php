<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('full_name')->nullable();
            $table->string('headline')->nullable(); // مثال: Backend Developer
            $table->text('summary')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('phone')->unique();
            $table->string('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('github')->nullable();
            $table->string('portfolio')->nullable();
            $table->string('cv_file')->nullable();
            $table->string('profile_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
