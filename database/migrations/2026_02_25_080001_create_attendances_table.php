<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'half_day', 'leave', 'holiday', 'weekend'])->default('present');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
