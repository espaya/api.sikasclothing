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
        Schema::create('shipment', function (Blueprint $table) {
            $table->id();
            $table->text('order_id');
            $table->text('carrier');
            $table->text('tracking_number');
            $table->text('status');
            $table->text('shipped_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment');
    }
};
