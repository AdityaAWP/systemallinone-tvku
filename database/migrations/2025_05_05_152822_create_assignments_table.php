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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->string('client');
            $table->string('spp_number');
            $table->string('spk_number')->nullable();
            $table->text('description');
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('marketing_expense', 15, 2)->nullable();
            $table->date('deadline');
            $table->text('notes')->nullable();
            $table->enum('type', ['free', 'paid', 'barter']);
            $table->enum('priority', ['normal', 'important', 'very_important'])->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'declined'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};