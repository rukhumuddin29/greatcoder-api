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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('enrollment_number', 50)->unique();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('enrolled_by')->constrained('users')->onDelete('cascade'); // BDE who closed it
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->decimal('course_price', 10, 2);
            $table->decimal('agreed_price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_reason')->nullable();

            $table->enum('status', ['active', 'completed', 'dropped', 'on_hold'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
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
        Schema::dropIfExists('enrollments');
    }
};
