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
    {
        Schema::create('lead_call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('called_by')->constrained('users')->onDelete('cascade');
            $table->string('call_outcome', 50); // Interested, No response, Busy, etc.
            $table->text('notes')->nullable();
            $table->integer('call_duration_seconds')->default(0);
            $table->date('next_follow_up')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_call_logs');
    }
};
