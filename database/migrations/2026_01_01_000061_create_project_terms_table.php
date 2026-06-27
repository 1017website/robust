<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');                          // DP, Termin 1, Pelunasan
            $table->decimal('percentage', 5, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status')->default('unpaid');     // unpaid, paid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_terms');
    }
};
