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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->string('user_identifier');
            $table->tinyInteger('rating')->unsigned(); // 1-10
            $table->text('review')->nullable();
            $table->timestamps();
            
            $table->index('book_id');
            $table->index('rating');
            $table->index('created_at');
            $table->index(['book_id', 'created_at']);
            $table->index('user_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
