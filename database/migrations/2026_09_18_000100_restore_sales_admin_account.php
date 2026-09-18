<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Akun yang dikonfirmasi pemilik; akun Sales lainnya tetap pada role saat ini.
        DB::table('users')
            ->where('email', 'admin@robust.test')
            ->where('role', 'sales')
            ->update(['role' => 'sales_admin', 'job_title' => 'Sales Admin']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'admin@robust.test')
            ->where('role', 'sales_admin')
            ->update(['role' => 'sales', 'job_title' => 'Sales']);
    }
};
