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
        Schema::create('student_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('hiring_companies');
            $table->string('job_title')->nullable();
            $table->string('status')->default('shortlisted'); // shortlisted, round_1, round_2, selected, rejected
            $table->date('interview_date')->nullable();
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
        Schema::dropIfExists('student_interviews');
    }
};
