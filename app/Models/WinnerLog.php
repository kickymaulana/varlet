<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WinnerLog extends Model
{
    protected $fillable = [
        'prize_id',
        'participant_id',
        'nomor_kupon',
        'nama_pemenang',
        'departemen',
        'lokasi_kerja',
        'drawn_at',
    ];

    protected $casts = [
        'drawn_at' => 'datetime',
    ];

    public function prize()
    {
        return $this->belongsTo(Prize::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function scopeSearchByName($query, $search)
    {
        if ($search) {
            return $query->where('nama_pemenang', 'like', "%{$search}%");
        }
        return $query;
    }
}
