<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->foreignId('quotation_id')->unique()->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('request_date')->nullable();
            $table->string('customer_po_number')->nullable();
            $table->string('customer_po_file')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('status')->default('submitted');
            $table->string('accurate_po_number')->nullable();
            $table->date('accurate_po_date')->nullable();
            $table->text('accurate_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_requests');
    }
};
