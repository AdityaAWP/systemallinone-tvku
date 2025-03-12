<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'position_id')) {
                $table->foreignId('position_id')->nullable()->constrained()->after('role');
            }
            if (!Schema::hasColumn('users', 'gender')) {
                $table->enum('gender', ['Laki-laki', 'Perempuan'])->nullable()->after('position_id');
            }
            if (!Schema::hasColumn('users', 'ktp')) {
                $table->string('ktp')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('ktp');
            }
            if (!Schema::hasColumn('users', 'birth')) {
                $table->date('birth')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'last_education')) {
                $table->string('last_education')->nullable()->after('birth');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('last_education');
            }
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }

            // Ubah kolom role menjadi nullable
            $table->string('role')->nullable()->change();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'position_id')) {
                $table->dropForeign(['position_id']);
                $table->dropColumn('position_id');
            }
            if (Schema::hasColumn('users', 'gender')) {
                $table->dropColumn('gender');
            }
            if (Schema::hasColumn('users', 'ktp')) {
                $table->dropColumn('ktp');
            }
            if (Schema::hasColumn('users', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('users', 'birth')) {
                $table->dropColumn('birth');
            }
            if (Schema::hasColumn('users', 'last_education')) {
                $table->dropColumn('last_education');
            }
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
            $table->string('role')->default('user')->change();
        });
    }
};
