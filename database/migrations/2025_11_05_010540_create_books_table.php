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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('isbn', 20);
            $table->foreignId('author_id')->constrained()->onDelete('cascade');
            $table->string('publisher', 200);
            $table->year('publication_year');
            $table->enum('availability_status', ['available', 'rented', 'reserved'])->default('available');
            $table->string('store_location', 100);
            $table->text('description')->nullable();
            $table->integer('price');
            $table->timestamps();
            
            $table->index('title');
            $table->index('author_id');
            $table->index('publication_year');
            $table->index('availability_status');
            $table->index(['author_id', 'availability_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
