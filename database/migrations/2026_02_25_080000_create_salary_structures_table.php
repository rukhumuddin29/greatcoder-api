<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('ctc_annual', 12, 2);
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('hra', 10, 2)->default(0);
            $table->decimal('da', 10, 2)->default(0);
            $table->decimal('special_allowance', 10, 2)->default(0);
            $table->decimal('pf_employer', 10, 2)->default(0);
            $table->decimal('esi_employer', 10, 2)->default(0);
            $table->decimal('other_allowances', 10, 2)->default(0);
            $table->date('effective_from');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
