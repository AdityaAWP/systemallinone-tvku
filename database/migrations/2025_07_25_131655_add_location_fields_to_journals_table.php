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
        Schema::table('journals', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('reason_of_absence');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('location_address')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_address']);
        });
    }
};
