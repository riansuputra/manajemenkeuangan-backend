<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        Admin::create([
            'name' => 'Admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('admindemo123'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        User::create([
            'name' => 'User Demo',
            'email' => 'user@demo.com',
            'password' => Hash::make('userdemo123'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}