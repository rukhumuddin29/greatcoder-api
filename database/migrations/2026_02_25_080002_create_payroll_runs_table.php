<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('month', 7); // YYYY-MM
            $table->integer('total_days_in_month')->default(30);
            $table->decimal('days_present', 5, 1)->default(0);
            $table->decimal('days_absent', 5, 1)->default(0);
            $table->decimal('days_half', 5, 1)->default(0);
            $table->integer('paid_leaves')->default(0);
            $table->integer('holidays')->default(0);
            $table->decimal('effective_working_days', 5, 1)->default(0); // present + half*0.5 + paid_leaves + holidays

            // Earnings
            $table->decimal('gross_salary', 10, 2)->default(0);
            $table->decimal('basic_earned', 10, 2)->default(0);
            $table->decimal('hra_earned', 10, 2)->default(0);
            $table->decimal('da_earned', 10, 2)->default(0);
            $table->decimal('special_earned', 10, 2)->default(0);
            $table->decimal('other_earned', 10, 2)->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);

            // Deductions
            $table->decimal('pf_employee', 10, 2)->default(0);
            $table->decimal('esi_employee', 10, 2)->default(0);
            $table->decimal('tds', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);

            // Net
            $table->decimal('net_salary', 10, 2)->default(0);

            // Status & Audit
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->onDelete('set null');
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'month']);
            $table->index(['month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
