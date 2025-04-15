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
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['incoming', 'outgoing']);
            $table->enum('category', ['internal', 'general'])->default('general');
            $table->string('number')->unique();
            $table->date('agenda_date');
            $table->date('letter_date');
            $table->string('agenda');
            $table->text('content');
            $table->string('sender')->nullable(); 
            $table->string('recipient')->nullable(); 
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
        
        Schema::create('letter_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->constrained('letters');
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
