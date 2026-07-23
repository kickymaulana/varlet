<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    protected $fillable = [
        'nama',
        'deskripsi',
        'urutan',
        'is_drawn',
    ];

    protected $casts = [
        'is_drawn' => 'boolean',
    ];

    public function winnerLogs()
    {
        return $this->hasMany(WinnerLog::class);
    }
}
