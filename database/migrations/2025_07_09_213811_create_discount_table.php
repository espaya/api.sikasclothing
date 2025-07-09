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
        Schema::create('discount', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['fixed', 'percentage']); // discount type
            $table->decimal('amount', 8, 2)->nullable(); // for fixed
            $table->decimal('percentage', 5, 2)->nullable(); // for percentage
            $table->decimal('minimum_value_order', 8, 2)->nullable();
            $table->decimal('maximum_discount', 8, 2)->nullable();
            $table->string('discount_code')->unique();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('status')->default(true); // active/inactive
            $table->integer('usage_limit')->nullable(); // max allowed usage
            $table->integer('used_count')->default(0); // how many times used
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount');
    }
};
