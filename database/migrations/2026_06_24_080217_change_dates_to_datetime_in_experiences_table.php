<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->dateTime('start_date')->change();
            $table->dateTime('end_date')->nullable()->change();
        });

        Schema::table('educations', function (Blueprint $table) {
            $table->dateTime('start_date')->change();
            $table->dateTime('end_date')->nullable()->change();
        });

        Schema::table('certifications', function (Blueprint $table) {
            $table->dateTime('issued_at')->change();
            $table->dateTime('expires_at')->nullable()->change();
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->dateTime('start_date')->nullable()->change();
            $table->dateTime('end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->date('start_date')->change();
            $table->date('end_date')->nullable()->change();
        });

        Schema::table('educations', function (Blueprint $table) {
            $table->date('start_date')->change();
            $table->date('end_date')->nullable()->change();
        });

        Schema::table('certifications', function (Blueprint $table) {
            $table->date('issued_at')->change();
            $table->date('expires_at')->nullable()->change();
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });
    }
};
