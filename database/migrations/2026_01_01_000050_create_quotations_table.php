<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();    // Q-2026-0018
            $table->foreignId('design_request_id')->nullable()->constrained('design_requests')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name');
            $table->string('pic_name')->nullable();
            $table->string('project_name');
            $table->foreignId('sales_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('delivery_method')->default('email'); // email, whatsapp, hardcopy
            $table->date('quote_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('priority')->default('medium');
            $table->string('currency')->default('IDR');
            $table->text('internal_note')->nullable();
            $table->text('customer_note')->nullable();
            // Harga
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->string('discount_type')->default('percent'); // percent, nominal
            $table->decimal('discount_value', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->decimal('tax_percent', 5, 2)->default(11);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->json('additional_costs')->nullable();    // pengiriman, instalasi, dll
            $table->decimal('additional_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('target_margin', 5, 2)->default(0);
            $table->string('status')->default('draft');      // draft, sent, negotiation, won, lost, expired
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
