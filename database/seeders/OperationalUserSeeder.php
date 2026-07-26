<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OperationalUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin Project',
                'email' => 'administration@robust.test',
                'role' => 'administration',
                'job_title' => 'Project Administration',
                'phone' => '081200000007',
            ],
            [
                'name' => 'Tim Quality Control',
                'email' => 'qc@robust.test',
                'role' => 'qc',
                'job_title' => 'Quality Control',
                'phone' => '081200000008',
            ],
            [
                'name' => 'Tim Delivery',
                'email' => 'delivery@robust.test',
                'role' => 'delivery',
                'job_title' => 'Delivery Coordinator',
                'phone' => '081200000009',
            ],
        ];

        foreach ($users as $data) {
            $user = User::withTrashed()->firstOrNew(['email' => $data['email']]);
            $user->fill($data + [
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);
            $user->deleted_at = null;
            $user->save();
        }
    }
}
