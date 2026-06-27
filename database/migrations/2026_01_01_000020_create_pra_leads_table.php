<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pra_leads', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('instansi');
            $table->string('pic_name');
            $table->string('pic_position')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('source')->default('whatsapp');   // whatsapp, website, referensi, telepon, email, lainnya
            $table->string('lab_type')->nullable();          // jenis laboratorium
            $table->string('location')->nullable();          // lokasi project / kota
            $table->text('initial_need')->nullable();        // kebutuhan awal
            $table->text('admin_note')->nullable();
            $table->decimal('est_value_min', 18, 2)->nullable();
            $table->decimal('est_value_max', 18, 2)->nullable();
            $table->string('priority')->default('medium');   // low, medium, high
            $table->string('status')->default('draft');      // draft, assigned, waiting_acceptance, accepted, rejected
            $table->foreignId('assigned_sales_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reject_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pra_leads');
    }
};
