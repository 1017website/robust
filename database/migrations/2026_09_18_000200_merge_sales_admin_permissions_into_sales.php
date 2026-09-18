<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'sales_admin')->where('job_title', 'Sales Admin')
            ->update(['job_title' => 'Sales']);
        DB::table('users')->where('role', 'sales_admin')->update(['role' => 'sales']);
    }

    public function down(): void
    {
        // Akun yang digabung tidak dapat dibedakan dari Sales lama tanpa cadangan data.
    }
};
