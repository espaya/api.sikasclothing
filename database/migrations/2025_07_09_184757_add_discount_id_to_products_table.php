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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_id')->nullable()->after('sale_price');
            $table->foreign('discount_id')->references('id')->on('discounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_id')->nullable()->after('sale_price');
            $table->foreign('discount_id')->references('id')->on('discounts')->nullOnDelete();
        });
    }
};
