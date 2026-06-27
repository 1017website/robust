<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->foreignId('pra_lead_id')->nullable()->constrained('pra_leads')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('instansi');
            $table->string('pic_name');
            $table->string('pic_position')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('location')->nullable();
            $table->string('city')->nullable();
            $table->string('instansi_type')->nullable();
            $table->string('source')->default('whatsapp');
            $table->string('reference')->nullable();
            $table->string('lab_name')->nullable();          // nama laboratorium / proyek
            $table->text('need_description')->nullable();
            $table->json('scope_items')->nullable();         // daftar kebutuhan: wall bench, fume hood, dll
            $table->string('capacity')->nullable();
            $table->decimal('est_value_min', 18, 2)->nullable();
            $table->decimal('est_value_max', 18, 2)->nullable();
            $table->string('priority')->default('medium');
            $table->string('stage')->default('lead');        // lead, design_request, penawaran, negosiasi, won, lost
            $table->string('status')->default('aktif');      // aktif, won, lost
            $table->text('initial_note')->nullable();
            $table->date('initial_followup_date')->nullable();
            $table->string('contact_preference')->nullable();
            $table->string('best_contact_time')->nullable();
            $table->foreignId('sales_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
