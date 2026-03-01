<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('leave_type', ['sick_leave', 'casual_leave', 'earned_leave']);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_half_day')->default(false);
            $table->enum('half_day_type', ['first_half', 'second_half'])->nullable();
            $table->decimal('total_days', 4, 1); // e.g. 1.5, 2, 0.5
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admin_remarks')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
