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
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('check_in_ip')->nullable()->after('check_in');
            $table->string('check_out_ip')->nullable()->after('check_out');
            $table->decimal('check_in_lat', 10, 7)->nullable()->after('check_in_ip');
            $table->decimal('check_in_lng', 10, 7)->nullable()->after('check_in_lat');
            $table->decimal('check_out_lat', 10, 7)->nullable()->after('check_out_ip');
            $table->decimal('check_out_lng', 10, 7)->nullable()->after('check_out_lat');
            $table->decimal('working_hours', 5, 2)->nullable()->after('check_out_lng');
            $table->integer('late_minutes')->default(0)->after('working_hours');
            $table->integer('overtime_minutes')->default(0)->after('late_minutes');
            $table->string('source')->default('admin')->after('overtime_minutes'); // self_checkin, admin, system_auto
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_ip',
                'check_out_ip',
                'check_in_lat',
                'check_in_lng',
                'check_out_lat',
                'check_out_lng',
                'working_hours',
                'late_minutes',
                'overtime_minutes',
                'source'
            ]);
        });
    }
};
