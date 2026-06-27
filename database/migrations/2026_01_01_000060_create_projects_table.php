<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();    // PRJ-2026-0062
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('planning');   // planning, ongoing, finishing, done, cancelled
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->integer('duration_days')->nullable();
            $table->string('work_method')->nullable();        // turnkey, dll
            $table->text('location')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->decimal('project_value', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_value', 18, 2)->default(0);
            $table->string('currency')->default('IDR');
            $table->string('payment_scheme')->nullable();     // tahap/bertahap, lunas, dll
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('internal_team')->nullable();
            $table->string('external_vendor')->nullable();
            $table->text('note')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
