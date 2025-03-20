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
        Schema::create('loan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->string('location');
            $table->date('booking_date');
            $table->time('start_booking');
            $table->date('return_date')->nullable();
            $table->string('producer_name');
            $table->string('producer_telp');   
            $table->string('crew_name');
            $table->string('crew_telp');
            $table->string('crew_division');
            $table->string('approver_name');
            $table->string('approver_telp');
            $table->enum('approval_status', ['Approve', 'Decline', 'Pending'])->default('Pending');
            $table->enum('return_status', ['Sudah Dikembalikan', 'Belum Dikembalikan'])->default('Belum Dikembalikan');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_items');
    }
};
