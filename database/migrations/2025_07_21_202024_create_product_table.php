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
        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->text('product_name');
            $table->text('category');
            $table->text('tags')->nullable();
            $table->string('gender');
            $table->text('custom_brand')->nullable();
            $table->text('description');
            $table->text('price')->nullable();
            $table->text('sale_price');
            $table->text('stock_quantity');
            $table->text('stock_status');
            $table->text('status');
            $table->text('color');
            $table->text('material');
            $table->text('fit_type');
            $table->string('size');
            $table->text('gallery');
            $table->text('featured');
            $table->text('discount');
            $table->string('barcode')->nullable();
            $table->text('slug');
            $table->text('sku');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
