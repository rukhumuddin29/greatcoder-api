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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 50)->unique();
            $table->foreignId('category_id')->constrained('expense_categories')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');

            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->enum('payment_mode', ['cash', 'bank_transfer', 'cheque', 'card'])->default('cash');
            $table->string('receipt_path')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('rejection_reason')->nullable();

            // Payroll specific fields
            $table->foreignId('payroll_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('payroll_month', 20)->nullable(); // YYYY-MM

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
