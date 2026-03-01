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
        Schema::create('bulk_emails', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->longText('body');
            $table->string('type'); // 'leads' or 'random'
            $table->string('target_status')->nullable(); // status if type is 'leads'
            $table->integer('recipients_count')->default(0);
            $table->foreignId('sent_by')->constrained('users');
            $table->string('status')->default('sent'); // sent, failed, pending
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_emails');
    }
};
