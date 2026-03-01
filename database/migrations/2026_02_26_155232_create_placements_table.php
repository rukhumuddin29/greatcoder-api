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
        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->unique()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('hiring_companies');
            $table->decimal('ctc_annual', 12, 2)->nullable();
            $table->date('join_date')->nullable();
            $table->string('designation')->nullable();
            $table->string('offer_letter_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placements');
    }
};
