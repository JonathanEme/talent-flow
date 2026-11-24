<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('interviewer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('type')->default('technical'); // screening, hr, technical, client, final
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(45);
            $table->string('location_or_link')->nullable(); // Google Meet / Zoom link o dirección física
            $table->string('status')->default('scheduled'); // scheduled, completed, canceled, no_show
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['scheduled_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
