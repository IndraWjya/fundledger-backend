<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Organisasi Default
        $org = Organization::create([
            'name'    => 'Himpunan Mahasiswa Informatika',
            'balance' => 15450000, // Saldo awal sesuai mockup frontend
        ]);

        // 2. Akun Maker 
        User::create([
            'name'            => 'Auf Japri',
            'email'           => 'bendahara@himtika.org',
            'password'        => Hash::make('password123'),
            'role'            => 'maker',
            'organization_id' => $org->id,
        ]);

        // 3. Akun Checker
        User::create([
            'name'            => 'Mfik Kaaga',
            'email'           => 'ketua@himtika.org',
            'password'        => Hash::make('password123'),
            'role'            => 'checker',
            'organization_id' => $org->id,
        ]);

        // 4. Akun Viewer
        User::create([
            'name'            => 'Rahmat Stang',
            'email'           => 'antek@himtika.org',
            'password'        => Hash::make('password123'),
            'role'            => 'viewer',
            'organization_id' => $org->id,
        ]);
    }
}