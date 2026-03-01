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
        Schema::create('employee_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('INDIA');

            // Contact
            $table->string('alternate_number')->nullable();
            $table->string('emergency_contact_number')->nullable();

            // Bank Details
            $table->string('account_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_details');
    }
};
