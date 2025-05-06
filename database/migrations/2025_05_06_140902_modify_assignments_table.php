<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('assignments', 'notes')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->renameColumn('notes', 'production_notes');
            });
        }

        Schema::table('assignments', function (Blueprint $table) {
            $table->date('created_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            if (Schema::hasColumn('assignments', 'production_notes')) {
                $table->renameColumn('production_notes', 'notes');
            }
            $table->dropColumn('created_date');
        });
    }
};
