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
        Schema::create('call_to_action', function (Blueprint $table) {
            $table->id();
            $table->text("title");
            $table->text("subtitle");
            $table->text("btn_text");
            $table->text("btn_url");
            $table->text("bg_image");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_to_action');
    }
};
