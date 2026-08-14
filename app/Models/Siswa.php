<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'kelas_id',
        'nama',
        'jenis_kelamin',
        'alamat',
        'saldo',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class, 'siswa_id');
    }
}
