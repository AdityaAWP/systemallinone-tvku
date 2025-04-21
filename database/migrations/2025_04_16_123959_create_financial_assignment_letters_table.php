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
    Schema::create('financial_assignment_letters', function (Blueprint $table) {
        $table->id();
        $table->string('reference_number');
        $table->string('title');
        $table->text('content');
        $table->date('letter_date');
        $table->string('created_by')->default('system');
        $table->boolean('manager_approval')->default(false);
        $table->timestamp('manager_approval_date')->nullable();
        $table->boolean('director_approval')->default(false);
        $table->timestamp('director_approval_date')->nullable();
        $table->enum('status', ['draft', 'pending_manager', 'pending_director', 'approved', 'rejected'])->default('draft');
        $table->text('notes')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_assignment_letters');
    }
};
