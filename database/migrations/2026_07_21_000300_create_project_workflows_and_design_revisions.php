<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('production_status')->default('stock');
            $table->boolean('production_report_completed')->default(false);
            $table->string('production_report_path')->nullable();
            $table->string('production_report_name')->nullable();
            $table->foreignId('production_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('production_updated_at')->nullable();

            $table->boolean('qc_completed')->default(false);
            $table->string('qc_document_path')->nullable();
            $table->string('qc_document_name')->nullable();
            $table->foreignId('qc_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('qc_updated_at')->nullable();

            $table->boolean('delivery_out_completed')->default(false);
            $table->string('delivery_out_photo_path')->nullable();
            $table->string('delivery_out_photo_name')->nullable();
            $table->boolean('delivery_returned_completed')->default(false);
            $table->string('delivery_returned_photo_path')->nullable();
            $table->string('delivery_returned_photo_name')->nullable();
            $table->foreignId('delivery_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivery_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('design_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->date('revision_date');
            $table->text('notes');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status')->default('submitted');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('status_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'revision_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_revisions');
        Schema::dropIfExists('project_workflows');
    }
};
