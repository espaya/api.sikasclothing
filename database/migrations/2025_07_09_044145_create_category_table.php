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
        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->text('name')->unique();
            $table->text('slug')->unique();
            $table->text('description');
            $table->text('image');
            $table->unsignedBigInteger('parent_id')->nullable(); // used for foreign key
            $table->foreign('parent_id')->references('id')->on('category')->onDelete('cascade');
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category');
    }
};
