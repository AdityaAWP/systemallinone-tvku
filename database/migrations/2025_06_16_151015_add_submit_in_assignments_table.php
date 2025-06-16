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
        Schema::table('assignments', function (Blueprint $table) {
            $table->enum('submit_status', ['belum', 'sudah'])
                  ->default('belum')
                  ->after('approval_status');
            $table->unsignedBigInteger('submitted_by')->nullable()->after('submit_status');
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn(['submit_status', 'submitted_by', 'submitted_at']);
        });
    }
};