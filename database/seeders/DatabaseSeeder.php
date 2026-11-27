<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat / Ambil Organisasi Default
        $org = Organization::firstOrCreate(
            ['name' => 'Himpunan Mahasiswa Sastra Mesin'],
            ['balance' => 15000]
        );

        // 2. Akun Maker (Bendahara)
        $maker = User::updateOrCreate(
            ['email' => 'bendahara@sastramesin.org'],
            [
                'name'            => 'Auf Japri',
                'password'        => Hash::make('password123'),
                'role'            => 'maker',
                'wallet_address'  => '0x483dB21e78419D4C2B19e4829cD7e820A142B21e',
                'organization_id' => $org->id,
            ]
        );

        // 3. Akun Checker (Ketua)
        $checker = User::updateOrCreate(
            ['email' => 'ketua@sastramesin.org'],
            [
                'name'            => 'Mfik Kaaga',
                'password'        => Hash::make('password123'),
                'role'            => 'checker',
                'wallet_address'  => '0x71Cab8294a61b9DeA92B10486c97a829B01ca19C',
                'organization_id' => $org->id,
            ]
        );

        // 4. Akun Viewer (Anggota)
        User::updateOrCreate(
            ['email' => 'antek@sastramesin.org'],
            [
                'name'            => 'Rahmat Stang',
                'password'        => Hash::make('password123'),
                'role'            => 'viewer',
                'wallet_address'  => '0x99201a91024bc98F8a72518e1194A2D4428cd442',
                'organization_id' => $org->id,
            ]
        );

        // 5. Data Transaksi Awal Terverifikasi On-Chain (Hanya dibuat jika belum ada)
        if (Transaction::where('organization_id', $org->id)->count() === 0) {
            Transaction::create([
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

            Transaction::create([
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

            Transaction::create([
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

            Transaction::create([
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
}
