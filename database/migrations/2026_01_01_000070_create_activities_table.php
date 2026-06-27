<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();    // ACT-250626-0012
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('sales_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');                          // meeting, call, survey_lokasi, presentasi, follow_up, whatsapp, email, penawaran
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('activity_date');
            $table->time('activity_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('location_link')->nullable();
            $table->string('pipeline_stage')->nullable();
            $table->string('status')->default('scheduled');  // scheduled, in_progress, completed, pending, cancelled
            $table->text('result')->nullable();
            $table->text('next_action')->nullable();
            $table->date('next_followup_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
