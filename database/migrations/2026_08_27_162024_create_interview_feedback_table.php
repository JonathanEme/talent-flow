<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_feedbacks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('interview_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('score'); // Escala 1 - 10
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('general_notes')->nullable();
            $table->string('recommendation')->default('approved'); // approved, rejected, on_hold

            $table->timestamps();

            $table->unique(['interview_id', 'user_id']); // Un feedback por usuario por entrevista
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_feedbacks');
    }
};
