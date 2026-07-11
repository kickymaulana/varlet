<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    // Daftarkan kolom yang boleh diisi secara massal saat seeder/insert berjalan
    protected $fillable = [
        'nomor_induk',
        'nama_lengkap',
        'nomor_hp',
        'lokasi_kerja',
        'departemen',
        'is_present',
        'nomor_kupon',
        'attended_at',
    ];

    // Opsional: Jika kolom attended_at ingin otomatis dikonversi menjadi object Carbon/Datetime
    protected $casts = [
        'is_present' => 'boolean',
        'attended_at' => 'datetime',
    ];
}

