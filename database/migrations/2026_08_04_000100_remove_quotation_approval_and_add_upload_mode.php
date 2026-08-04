<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'creation_mode')) {
                $table->string('creation_mode', 20)->default('builder')->after('currency');
            }
        });

        // Approval SPV sudah tidak menjadi gate. Data yang masih tertahan pada
        // workflow lama langsung dipindahkan ke status siap dikirim.
        DB::table('quotations')
            ->whereIn('status', ['waiting_approval', 'revision', 'rejected', 'approved'])
            ->update(['status' => 'ready']);
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'creation_mode')) {
                $table->dropColumn('creation_mode');
            }
        });
    }
};
