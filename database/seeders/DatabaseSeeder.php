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
            'name'    => 'Himpunan Mahasiswa Sastra Mesin',
            'balance' => 15000, 
        ]);

        // 2. Akun Maker 
        $maker = User::create([
            'name'            => 'Auf Japri',
            'email'           => 'bendahara@sastramesin.org',
            'password'        => Hash::make('password123'),
            'role'            => 'maker',
            'organization_id' => $org->id,
        ]);

        // 3. Akun Checker
        $checker = User::create([
            'name'            => 'Mfik Kaaga',
            'email'           => 'ketua@sastramesin.org',
            'password'        => Hash::make('password123'),
            'role'            => 'checker',
            'organization_id' => $org->id,
        ]);

        // 4. Akun Viewer
        User::create([
            'name'            => 'Rahmat Stang',
            'email'           => 'antek@sastramesin.org',
            'password'        => Hash::make('password123'),
            'role'            => 'viewer',
            'organization_id' => $org->id,
        ]);

        // 5. Data Transaksi Awal Terverifikasi On-Chain
        \App\Models\Transaction::create([
            'organization_id' => $org->id,
            'maker_id'        => $maker->id,
            'checker_id'      => $checker->id,
            'type'            => 'cash_in',
            'category'        => 'Iuran Anggota',
            'amount'          => 5000000,
            'description'     => 'Iuran kas bulanan pengurus HIMASASIN periode Oktober 2026',
            'status'          => 'completed',
            'tx_hash'         => '0x8f2d8471c63b4f65c97ea875d9c221e289f66141a029c78d52187f58f553a1a4',
        ]);

        \App\Models\Transaction::create([
            'organization_id' => $org->id,
            'maker_id'        => $maker->id,
            'checker_id'      => $checker->id,
            'type'            => 'cash_in',
            'category'        => 'Sponsorship',
            'amount'          => 12000000,
            'description'     => 'Dana sponsor utama Dies Natalis dari PT Tech Nusantara',
            'status'          => 'completed',
            'tx_hash'         => '0xb341907efc89d15c7e099a54a93c78d52187f58f553a1a45486d34bcf1201948',
        ]);

        \App\Models\Transaction::create([
            'organization_id' => $org->id,
            'maker_id'        => $maker->id,
            'checker_id'      => $checker->id,
            'type'            => 'cash_out',
            'category'        => 'Perlengkapan',
            'amount'          => 1500000,
            'description'     => 'Pembelian sound system portable dan kabel roll untuk rapat akbar',
            'status'          => 'completed',
            'tx_hash'         => '0x4c29ea71c99182bb193a0021c78d52187f58f553a1a494726bf10927da558712',
        ]);

        \App\Models\Transaction::create([
            'organization_id' => $org->id,
            'maker_id'        => $maker->id,
            'checker_id'      => null,
            'type'            => 'cash_out',
            'category'        => 'Operasional',
            'amount'          => 1200000,
            'description'     => 'Sewa ruang seminar gedung rektorat untuk Tech Talk #3',
            'status'          => 'pending',
            'tx_hash'         => null,
        ]);
    }
}