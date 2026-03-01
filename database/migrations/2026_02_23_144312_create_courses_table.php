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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable(); // IT, Marketing, etc.
            $table->enum('mode', ['online', 'offline', 'hybrid'])->default('offline');
            $table->integer('duration_weeks')->nullable();
            $table->integer('total_sessions')->nullable();
            $table->decimal('original_price', 10, 2);
            $table->decimal('offer_price', 10, 2);
            $table->boolean('is_negotiable')->default(true);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
