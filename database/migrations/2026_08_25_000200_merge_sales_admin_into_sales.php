<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereRaw('LOWER(role) = ?', ['sales_admin'])
            ->whereRaw('LOWER(job_title) = ?', ['sales admin'])
            ->update(['job_title' => 'Sales']);

        DB::table('users')
            ->whereRaw('LOWER(role) = ?', ['sales_admin'])
            ->update(['role' => 'sales']);
    }

    public function down(): void
    {
        // Penggabungan role tidak dapat dibalik secara aman karena tidak ada lagi
        // penanda untuk membedakan Sales lama dengan eks Sales Admin.
    }
};
