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
    {Schema::create('workflow_rules', function (Blueprint $table) {
        $table->id();

        $table->foreignId('workflow_id')
            ->constrained('hiring_workflows')
            ->onDelete('cascade');
        $table->string('name');

        $table->string('field');

        $table->string('operator');

        $table->string('value');

        $table->string('action');
        $table->integer('score_weight')->default(0);
        $table->integer('priority')->default(0);
        $table->string('group_logic')->default('AND'); // AND / OR
        $table->foreignId('target_stage_id')
            ->nullable()
            ->constrained('workflow_stages')
            ->nullOnDelete();

        $table->timestamps();
    });
    }


    public function down(): void
    {
        Schema::dropIfExists('workflow_rules');
    }
};
