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
        Schema::table('overtimes', function (Blueprint $table) {
            $table->time('normal_work_time_check_in')->nullable()->change();
            $table->time('normal_work_time_check_out')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->time('normal_work_time_check_in')->nullable(false)->change();
            $table->time('normal_work_time_check_out')->nullable(false)->change();
        });
    }
};
