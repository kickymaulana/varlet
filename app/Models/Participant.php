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
        'eligible_for_draw',
    ];

    protected $casts = [
        'is_present' => 'boolean',
        'eligible_for_draw' => 'boolean',
        'attended_at' => 'datetime',
    ];
}

