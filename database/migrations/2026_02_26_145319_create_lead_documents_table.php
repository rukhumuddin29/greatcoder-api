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
        Schema::create('lead_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->string('name');
            $table->string('file_path');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->string('category'); // id_proof, certificate, photo, resume, other
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['lead_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_documents');
    }
};
