<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_requirements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('position_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('skill_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('is_mandatory')->default(true);
            $table->unsignedTinyInteger('min_years_experience')->default(1);
            $table->string('expected_level')->nullable(); // beginner, intermediate, advanced, expert

            $table->timestamps();

            $table->unique(['position_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_requirements');
    }
};