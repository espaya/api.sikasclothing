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
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->text('order_number');
            $table->text('user_id');
            $table->text('guest_email');
            $table->text('shipping_address_id');
            $table->text('billing_address_id');
            $table->text('status');
            $table->text('payment_status');
            $table->text('payment_method');
            $table->text('total_amount');
            $table->text('subtotal_amount');
            $table->text('tax_amount');
            $table->text('discount_amount');
            $table->text('shipping_amount');
            $table->text('shipping_method');
            $table->text('tracking_number');
            $table->text('currency');
            $table->text('notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order');
    }
};
