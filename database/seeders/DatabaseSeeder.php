<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Admin
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@tabungan.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Seed Guru
        $guru = User::create([
            'name' => 'Budi Utomo, S.Pd.',
            'email' => 'guru@tabungan.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        $guru2 = User::create([
            'name' => 'Rina Wulandari, S.Pd.',
            'email' => 'guru2@tabungan.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        // 3. Seed Classes (each with its own wali kelas)
        $kelas1A = Kelas::create([
            'nama_kelas' => 'Kelas 1-A',
            'tahun_ajaran' => '2025/2026',
            'wali_kelas_id' => $guru->id,
        ]);

        $kelas1B = Kelas::create([
            'nama_kelas' => 'Kelas 1-B',
            'tahun_ajaran' => '2025/2026',
            'wali_kelas_id' => $guru2->id,
        ]);


        // 5. Seed Students
        $siswa1 = Siswa::create([
            'kelas_id' => $kelas1A->id,
            'nis' => '20260001',
            'nama' => 'Ahmad Fauzi',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jl. Merdeka No. 12, Bandung',
            'saldo' => 40000.00,
        ]);

        $siswa2 = Siswa::create([
            'kelas_id' => $kelas1A->id,
            'nis' => '20260002',
            'nama' => 'Siti Aminah',
            'jenis_kelamin' => 'P',
            'alamat' => 'Jl. Mawar No. 5, Bandung',
            'saldo' => 100000.00,
        ]);

        $siswa3 = Siswa::create([
            'kelas_id' => $kelas1B->id,
            'nis' => '20260003',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jl. Melati No. 8, Bandung',
            'saldo' => 0.00,
        ]);

        $siswa4 = Siswa::create([
            'kelas_id' => $kelas1B->id,
            'nis' => '20260004',
            'nama' => 'Citra Ayu',
            'jenis_kelamin' => 'P',
            'alamat' => 'Jl. Merdeka No. 12, Bandung',
            'saldo' => 25000.00,
        ]);

        // 6. Seed Transactions
        // Ahmad Fauzi Transactions
        Transaksi::create([
            'siswa_id' => $siswa1->id,
            'user_id' => $admin->id,
            'tipe' => 'setor',
            'jumlah' => 50000.00,
            'tanggal' => now()->subDays(2)->format('Y-m-d'),
            'keterangan' => 'Setoran awal',
        ]);

        Transaksi::create([
            'siswa_id' => $siswa1->id,
            'user_id' => $guru->id,
            'tipe' => 'tarik',
            'jumlah' => 10000.00,
            'tanggal' => now()->subDay()->format('Y-m-d'),
            'keterangan' => 'Beli buku tulis',
        ]);

        // Siti Aminah Transactions
        Transaksi::create([
            'siswa_id' => $siswa2->id,
            'user_id' => $guru->id,
            'tipe' => 'setor',
            'jumlah' => 100000.00,
            'tanggal' => now()->subDay()->format('Y-m-d'),
            'keterangan' => 'Tabungan pertama',
        ]);

        // Citra Ayu Transactions
        Transaksi::create([
            'siswa_id' => $siswa4->id,
            'user_id' => $guru2->id,
            'tipe' => 'setor',
            'jumlah' => 25000.00,
            'tanggal' => now()->subDay()->format('Y-m-d'),
            'keterangan' => 'Setoran awal',
        ]);
    }
}
