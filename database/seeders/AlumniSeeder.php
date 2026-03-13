<?php

namespace Database\Seeders;

use App\Models\Alumni;
use Illuminate\Database\Seeder;

class AlumniSeeder extends Seeder
{
    public function run(): void
    {
        $alumni = [
            [
                'nim' => '202010370311170',
                'nama_lengkap' => 'Muhammad Rizky Pratama',
                'nama_variasi' => ['M. Rizky', 'Muh. Rizky Pratama'],
                'email' => 'rizky.pratama@gmail.com',
                'no_telepon' => '081234567890',
                'prodi' => 'Informatika',
                'fakultas' => 'Fakultas Teknik',
                'tahun_masuk' => 2020,
                'tahun_lulus' => 2024,
                'tracking_status' => 'belum_dilacak',
            ],
            [
                'nim' => '202010370311171',
                'nama_lengkap' => 'Siti Nurhaliza',
                'nama_variasi' => ['Siti N.', 'S. Nurhaliza'],
                'email' => 'siti.nurhaliza@gmail.com',
                'no_telepon' => '081234567891',
                'prodi' => 'Informatika',
                'fakultas' => 'Fakultas Teknik',
                'tahun_masuk' => 2020,
                'tahun_lulus' => 2024,
                'tracking_status' => 'auto_verified',
            ],
            [
                'nim' => '201910370311150',
                'nama_lengkap' => 'Ahmad Fauzan Hakim',
                'nama_variasi' => ['A. Fauzan', 'Ahmad F. Hakim'],
                'email' => null,
                'no_telepon' => null,
                'prodi' => 'Sistem Informasi',
                'fakultas' => 'Fakultas Teknik',
                'tahun_masuk' => 2019,
                'tahun_lulus' => 2023,
                'tracking_status' => 'needs_audit',
            ],
            [
                'nim' => '201910370311155',
                'nama_lengkap' => 'Dewi Anggraini',
                'nama_variasi' => null,
                'email' => 'dewi.anggraini@yahoo.com',
                'no_telepon' => '082345678901',
                'prodi' => 'Teknik Elektro',
                'fakultas' => 'Fakultas Teknik',
                'tahun_masuk' => 2019,
                'tahun_lulus' => 2023,
                'tracking_status' => 'not_found',
            ],
            [
                'nim' => '201810370311140',
                'nama_lengkap' => 'Budi Santoso',
                'nama_variasi' => ['B. Santoso'],
                'email' => 'budi.santoso@outlook.com',
                'no_telepon' => '083456789012',
                'prodi' => 'Informatika',
                'fakultas' => 'Fakultas Teknik',
                'tahun_masuk' => 2018,
                'tahun_lulus' => 2022,
                'tracking_status' => 'auto_verified',
            ],
            [
                'nim' => '202110370311180',
                'nama_lengkap' => 'Putri Rahayu Wulandari',
                'nama_variasi' => ['Putri R.W.', 'P. Rahayu'],
                'email' => null,
                'no_telepon' => null,
                'prodi' => 'Informatika',
                'fakultas' => 'Fakultas Teknik',
                'tahun_masuk' => 2021,
                'tahun_lulus' => 2025,
                'tracking_status' => 'belum_dilacak',
            ],
            [
                'nim' => '202010370311175',
                'nama_lengkap' => 'Farhan Dwi Cahyono',
                'nama_variasi' => ['Farhan D.C.'],
                'email' => 'farhan.cahyono@gmail.com',
                'no_telepon' => '084567890123',
                'prodi' => 'Sistem Informasi',
                'fakultas' => 'Fakultas Teknik',
                'tahun_masuk' => 2020,
                'tahun_lulus' => 2024,
                'tracking_status' => 'belum_dilacak',
            ],
            [
                'nim' => '201810370311145',
                'nama_lengkap' => 'Rina Marlina Sari',
                'nama_variasi' => ['Rina M.S.'],
                'email' => 'rina.marlina@gmail.com',
                'no_telepon' => '085678901234',
                'prodi' => 'Teknik Elektro',
                'fakultas' => 'Fakultas Teknik',
                'tahun_masuk' => 2018,
                'tahun_lulus' => 2022,
                'tracking_status' => 'insufficient_data',
            ],
        ];

        foreach ($alumni as $data) {
            Alumni::updateOrCreate(
                ['nim' => $data['nim']],
                $data
            );
        }
    }
}
