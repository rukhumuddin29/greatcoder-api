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
        Schema::table('lead_call_logs', function (Blueprint $table) {
            $table->string('channel', 20)->default('phone')->after('called_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_call_logs', function (Blueprint $table) {
            $table->dropColumn('channel');
        });
    }
};
