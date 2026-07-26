<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pra_leads', function (Blueprint $table) {
            $table->string('division')->nullable()->after('instansi');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('division')->nullable()->after('instansi');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('division');
        });

        Schema::table('pra_leads', function (Blueprint $table) {
            $table->dropColumn('division');
        });
    }
};
