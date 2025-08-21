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
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('country', 100);
            $table->string('country_code', 2)->index();
            $table->string('state_code', 10)->nullable()->index();
            $table->string('tax_name', 100);
            $table->string('tax_type', 50)->default('VAT'); // VAT, GST, Sales Tax, etc.
            $table->decimal('rate', 5, 2)->default(0); // Max 999.99%
            $table->date('effective_date')->default(now());
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'scheduled'])->default('active');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // For additional data like reduced rates, exemptions
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            // Indexes for performance
            $table->unique(['country_code', 'state_code', 'effective_date'], 'unique_tax_rate');
            $table->index(['status', 'effective_date']);
            $table->index(['country_code', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
