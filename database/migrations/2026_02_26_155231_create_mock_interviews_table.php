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
        Schema::create('mock_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');
            $table->foreignId('interviewer_id')->constrained('users');
            $table->dateTime('scheduled_at');
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled
            $table->integer('technical_score')->nullable(); // 1-10
            $table->integer('behavioral_score')->nullable(); // 1-10
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mock_interviews');
    }
};
