<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;

class ParticipantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Kosongkan tabel terlebih dahulu agar tidak duplikat jika dijalankan ulang
        DB::table('participants')->truncate();

        // 2. Arahkan ke lokasi file CSV yang disimpan
        $csvFile = database_path('data/RSVP.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("File RSVP.csv tidak ditemukan di folder database/data/ !");
            return;
        }

        // 3. Buka dan baca file CSV
        $file = fopen($csvFile, 'r');

        // Lewati baris pertama (header kolom)
        $header = fgetcsv($file);

        $count = 0;

        while (($row = fgetcsv($file)) !== FALSE) {
            // Mapping index berdasarkan struktur file RSVP.csv kamu:
            // 0: Timestamp, 1: Nama Lengkap, 2: NIK, 3: No HP, 4: Lokasi Kerja, 5: Departemen, 6: Kehadiran

            $statusKehadiran = $row[6] ?? '';

            // Hanya import data yang berniat hadir saja
            if (trim($statusKehadiran) === 'Ya, saya akan hadir') {

                Participant::create([
                    // trim() digunakan untuk menghapus spasi tak sengaja di awal/akhir teks (seperti pada NIK "D240027 ")
                    'nomor_induk'  => trim($row[2]),
                    'nama_lengkap' => trim($row[1]),
                    'nomor_hp'     => trim($row[3]),
                    'lokasi_kerja' => trim($row[4]),
                    'departemen'   => trim($row[5]),
                    'is_present'   => false, // Default belum check-in di lokasi
                    'nomor_kupon'  => null,  // Baru didapat saat check-in nanti
                ]);

                $count++;
            }
        }

        fclose($file);
        $this->command->info("Sukses meng-import {$count} data karyawan ke tabel participants!");
    }
}
