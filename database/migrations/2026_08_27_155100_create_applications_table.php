<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('position_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('candidate_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('status')->default('active'); // active, hired, rejected, withdrawn
            $table->string('stage')->default('new');     // new, screening, technical_interview, final_interview, offer
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->date('applied_at');

            $table->timestamps();

            // Un candidato no puede postularse dos veces a la misma posición
            $table->unique(['position_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
