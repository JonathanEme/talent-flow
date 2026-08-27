<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->decimal('salary_offered', 12, 2);
            $table->string('currency', 10)->default('USD');
            $table->date('start_date');
            $table->date('expiration_date')->nullable();
            
            $table->text('benefits_summary')->nullable();
            $table->text('terms_and_conditions')->nullable();
            
            $table->string('status')->default('draft'); // draft, sent, accepted, declined
            $table->text('decline_reason')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            $table->index(['application_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};