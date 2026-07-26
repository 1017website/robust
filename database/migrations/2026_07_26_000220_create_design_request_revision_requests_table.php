<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_request_revision_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->text('notes');
            $table->string('status')->default('requested');
            $table->json('snapshot')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('drawing_uploaded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['design_request_id', 'revision_number'], 'dr_revision_request_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_request_revision_requests');
    }
};
