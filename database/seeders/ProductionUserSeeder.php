<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::withTrashed()->firstOrNew([
            'email' => 'production@robust.test',
        ]);

        $user->fill([
            'name' => 'Tim Produksi',
            'password' => Hash::make('password'),
            'role' => 'production',
            'job_title' => 'Production Engineer',
            'phone' => '081200000006',
            'is_active' => true,
        ]);
        $user->deleted_at = null;
        $user->save();
    }
}
