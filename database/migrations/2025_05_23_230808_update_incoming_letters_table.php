<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('incoming_letters', function (Blueprint $table) {
            // Tambahkan kolom baru jika belum ada
            if (!Schema::hasColumn('incoming_letters', 'letter_number')) {
                $table->string('letter_number')->nullable()->after('sender');
            }

            // Ubah tipe enum jika perlu
            if (Schema::hasColumn('incoming_letters', 'type')) {
                Schema::table('incoming_letters', function (Blueprint $table) {
                    $table->enum('type', ['internal', 'general', 'visit'])
                        ->default('general')
                        ->change();
                });
            }

            // Ubah nama kolom jika perlu (contoh)
            // if (Schema::hasColumn('incoming_letters', 'old_column_name')) {
            //     $table->renameColumn('old_column_name', 'new_column_name');
            // }
        });
    }

    public function down()
    {
        Schema::table('incoming_letters', function (Blueprint $table) {
            // Rollback perubahan
            $table->dropColumn('letter_number');
            
            // Untuk rollback perubahan enum
            // $table->enum('type', ['internal', 'general'])
            //     ->default('general')
            //     ->change();
        });
    }
};