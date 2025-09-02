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
        Schema::table('billing_address', function (Blueprint $table) {
             $table->renameColumn('default', 'is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_address', function (Blueprint $table) {
            $table->renameColumn('is_default', 'default');
        });
    }
};
