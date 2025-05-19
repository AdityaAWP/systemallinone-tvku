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
        Schema::create('interns', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // used for matching during registration
            $table->date('birth_date')->nullable();
            $table->string('email')->unique()->nullable(); // not always known at pre-registration
            $table->foreignId('school_id')->nullable()->constrained('intern_schools')->onDelete('cascade');
            $table->enum('division', [
                'IT', 'Produksi', 'DINUS FM', 'TS', 'MCR', 'DMO', 'Wardrobe', 'News', 'Humas dan Marketing'
            ])->nullable();
            $table->string('nis_nim')->nullable();
            $table->string('no_phone')->nullable();
            $table->string('institution_supervisor')->nullable();
            $table->string('college_supervisor')->nullable();
            $table->string('college_supervisor_phone')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('password')->nullable(); // allow null at pre-reg; will be filled when intern registers
            $table->timestamps();
        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interns');
    }
};
