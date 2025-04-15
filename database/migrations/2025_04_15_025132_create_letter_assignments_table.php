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
        Schema::create('letter_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['draft', 'approved_by_manager', 'approved_by_director', 'rejected']);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('manager_approval_id')->nullable()->constrained('users');
            $table->foreignId('director_approval_id')->nullable()->constrained('users');
            $table->timestamp('manager_approved_at')->nullable();
            $table->timestamp('director_approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_assignments');
    }
};
