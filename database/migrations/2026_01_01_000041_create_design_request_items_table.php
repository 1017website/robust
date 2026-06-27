<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_request_id')->constrained()->cascadeOnDelete();
            $table->string('category')->nullable();          // Wall Bench, Fume Hood, dst
            $table->string('name');
            $table->text('specification')->nullable();
            $table->decimal('qty', 12, 2)->default(1);
            $table->string('unit')->default('Unit');
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('margin', 5, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_request_items');
    }
};
