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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();

            $table->enum('lead_type', ['student', 'professional', 'other'])->default('student');
            $table->string('source', 100)->nullable(); // facebook, walking, referral
            $table->string('referred_by', 150)->nullable();

            // Student/Education Details
            $table->string('school_name', 200)->nullable();
            $table->year('tenth_year')->nullable();
            $table->string('tenth_board', 100)->nullable();
            $table->decimal('tenth_percentage', 5, 2)->nullable();
            $table->string('tenth_grade', 10)->nullable();

            $table->string('inter_college', 200)->nullable();
            $table->year('inter_year')->nullable();
            $table->string('inter_board', 100)->nullable();
            $table->string('inter_stream', 100)->nullable();
            $table->decimal('inter_percentage', 5, 2)->nullable();
            $table->string('inter_grade', 10)->nullable();

            $table->string('degree_college', 200)->nullable();
            $table->year('degree_year')->nullable();
            $table->string('degree_name', 150)->nullable(); // B.Tech, B.Com
            $table->string('degree_specialization', 150)->nullable();
            $table->string('degree_university', 200)->nullable();
            $table->decimal('degree_percentage', 5, 2)->nullable();
            $table->string('degree_grade', 10)->nullable();

            $table->string('pg_college', 200)->nullable();
            $table->year('pg_year')->nullable();
            $table->string('pg_name', 150)->nullable();
            $table->string('pg_specialization', 150)->nullable();
            $table->string('pg_university', 200)->nullable();
            $table->decimal('pg_percentage', 5, 2)->nullable();
            $table->string('pg_grade', 10)->nullable();

            // Professional Details
            $table->string('current_company', 200)->nullable();
            $table->string('current_designation', 150)->nullable();
            $table->integer('experience_years')->nullable();
            $table->text('current_skills')->nullable();

            // Status & Assignment
            $table->enum('status', [
                'new', 'assigned', 'contacted', 'interested',
                'not_interested', 'callback', 'demo_scheduled',
                'converted', 'lost'
            ])->default('new');

            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
