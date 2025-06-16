<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Ubah enum approval_status untuk menambah 'submitted' dan 'rejected'
            DB::statement("ALTER TABLE assignments MODIFY COLUMN approval_status ENUM('pending', 'submitted', 'approved', 'declined', 'rejected') DEFAULT 'pending'");
            
            // Tambah kolom untuk tracking submission
            $table->unsignedBigInteger('submitted_by')->nullable()->after('approved_at');
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            
            // Tambah foreign key untuk submitted_by
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Drop foreign key dan kolom
            $table->dropForeign(['submitted_by']);
            $table->dropColumn(['submitted_by', 'submitted_at']);
            
            // Kembalikan enum ke kondisi semula
            DB::statement("ALTER TABLE assignments MODIFY COLUMN approval_status ENUM('pending', 'approved', 'declined') DEFAULT 'pending'");
        });
    }
};
