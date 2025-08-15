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
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('title');                 // Display name
            $table->enum('source_type', ['category', 'custom']); // Where the link comes from
            $table->unsignedBigInteger('source_id')->nullable(); // category_id if category
            $table->string('custom_url')->nullable();            // For custom link
            $table->enum('location', ['topbar', 'main', 'footer']); // Menu position
            $table->unsignedBigInteger('parent_id')->nullable(); // For dropdowns/nesting
            $table->integer('order')->default(0);   // Sort order
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
