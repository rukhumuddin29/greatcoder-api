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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->foreignId('received_by')->constrained('users')->onDelete('cascade');

            $table->decimal('amount', 10, 2);
            $table->enum('payment_mode', ['cash', 'bank_transfer', 'upi', 'cheque', 'card'])->default('cash');
            $table->string('transaction_reference')->nullable();
            $table->date('payment_date');
            $table->enum('payment_type', ['down_payment', 'installment', 'full_payment', 'other'])->default('installment');
            $table->integer('installment_number')->nullable();

            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_reason')->nullable();
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
        Schema::dropIfExists('payments');
    }
};
