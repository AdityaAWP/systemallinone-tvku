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
        Schema::table('interns', function (Blueprint $table) {
            // Add institution type field
            $table->enum('institution_type', ['Perguruan Tinggi', 'SMA/SMK'])->nullable()->after('school_id');
            
            // Add foreign key for intern division
            $table->foreignId('intern_division_id')->nullable()->constrained('intern_divisions')->onDelete('set null')->after('division');
            
            // Make division nullable since we'll use intern_division_id instead
            $table->string('division')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interns', function (Blueprint $table) {
            $table->dropForeign(['intern_division_id']);
            $table->dropColumn(['institution_type', 'intern_division_id']);
            
            // Restore division as required enum
            $table->enum('division', [
                'IT', 'Produksi', 'DINUS FM', 'TS', 'MCR', 'DMO', 'Wardrobe', 'News', 'Humas dan Marketing'
            ])->nullable(false)->change();
        });
    }
};
