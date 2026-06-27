<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();    // DR-026
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name');
            $table->string('pic_name')->nullable();
            $table->string('project_name');                  // nama proyek / laboratorium
            $table->foreignId('sales_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('request_date')->nullable();
            $table->date('deadline')->nullable();
            $table->string('priority')->default('medium');
            $table->text('short_description')->nullable();
            $table->string('lab_type')->nullable();
            $table->string('capacity')->nullable();
            $table->text('detail_need')->nullable();
            $table->json('scope_checklist')->nullable();     // wall bench, island bench, fume hood, dst
            $table->json('outputs')->nullable();             // layout_2d, rendering_3d, shop_drawing, boq, cost_estimation
            $table->text('extra_note')->nullable();
            // Produksi / drafter
            $table->foreignId('production_pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('production_note')->nullable();
            $table->string('status')->default('draft');      // draft, assigned, drafting, costing, review, completed, rejected
            $table->unsignedTinyInteger('progress')->default(0);
            // Feedback teknis dari drafter
            $table->json('dimensions')->nullable();
            $table->json('materials')->nullable();
            $table->json('accessories')->nullable();
            $table->json('material_estimation')->nullable();
            $table->decimal('cost_material', 18, 2)->nullable();
            $table->decimal('cost_production', 18, 2)->nullable();
            $table->decimal('cost_installation', 18, 2)->nullable();
            $table->decimal('cost_total', 18, 2)->nullable();
            $table->text('technical_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_requests');
    }
};
