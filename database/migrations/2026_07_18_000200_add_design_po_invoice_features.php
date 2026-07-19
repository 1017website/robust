<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_masters', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('category')->nullable();
            $table->string('name');
            $table->string('variant')->nullable();
            $table->text('specification')->nullable();
            $table->string('unit', 50)->default('Unit');
            $table->decimal('default_cost_price', 18, 2)->default(0);
            $table->decimal('default_margin', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->foreignId('item_master_id')->nullable()->after('quotation_id')->constrained('item_masters')->nullOnDelete();
            $table->string('variant')->nullable()->after('name');
        });

        Schema::table('design_request_items', function (Blueprint $table) {
            $table->foreignId('item_master_id')->nullable()->after('design_request_id')->constrained('item_masters')->nullOnDelete();
            $table->string('variant')->nullable()->after('name');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('area')->nullable()->after('city');
            $table->string('division')->nullable()->after('area');
        });

        Schema::table('purchase_order_requests', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('quotation_id')->constrained('customers')->nullOnDelete();
            $table->string('project_number')->nullable()->after('code');
            $table->string('customer_name')->nullable()->after('customer_id');
            $table->string('customer_area')->nullable()->after('customer_name');
            $table->string('customer_division')->nullable()->after('customer_area');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('parent_document_id')->nullable()->after('id')->constrained('documents')->nullOnDelete();
            $table->unsignedInteger('revision_number')->default(1)->after('version');
            $table->boolean('is_current')->default(true)->after('revision_number');
            $table->text('revision_note')->nullable()->after('is_current');
            $table->index(['parent_document_id', 'revision_number']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('purchase_order_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('invoice_date');
            $table->string('customer_name');
            $table->string('project_number')->nullable();
            $table->string('project_name');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('installation_amount', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('paid_total', 18, 2)->default(0);
            $table->string('status')->default('draft');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoice_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('term_number');
            $table->string('description')->nullable();
            $table->decimal('percentage', 5, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->date('issued_date')->nullable();
            $table->string('accurate_invoice_number')->nullable();
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->string('status')->default('planned');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['invoice_id', 'term_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_terms');
        Schema::dropIfExists('invoices');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_document_id');
            $table->dropColumn(['revision_number', 'is_current', 'revision_note']);
        });
        Schema::table('purchase_order_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn(['project_number', 'customer_name', 'customer_area', 'customer_division']);
        });
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn(['area', 'division']));
        Schema::table('design_request_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('item_master_id');
            $table->dropColumn('variant');
        });
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('item_master_id');
            $table->dropColumn('variant');
        });
        Schema::dropIfExists('item_masters');
    }
};
