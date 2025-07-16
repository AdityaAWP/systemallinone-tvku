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
        Schema::create('intern_supervisor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intern_id')->constrained('interns')->onDelete('cascade');
            $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // Untuk menandai pembimbing utama
            $table->text('notes')->nullable(); // Catatan khusus tentang pembimbingan
            $table->date('assigned_date')->default(now()); // Tanggal mulai pembimbingan
            $table->date('ended_date')->nullable(); // Tanggal berakhir pembimbingan
            $table->timestamps();

            // Unique constraint untuk mencegah duplikasi
            $table->unique(['intern_id', 'supervisor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intern_supervisor');
    }
};
