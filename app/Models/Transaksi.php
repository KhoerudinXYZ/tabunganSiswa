<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'user_id',
        'tipe',
        'jumlah',
        'tanggal',
        'keterangan',
        'is_reversal',
        'reversal_of_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
        'is_reversal' => 'boolean',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The original transaction this entry reverses (only set when is_reversal is true).
     */
    public function reversalOf()
    {
        return $this->belongsTo(Transaksi::class, 'reversal_of_id');
    }

    /**
     * The reversal entry that voids this transaction, if any.
     */
    public function reversalEntry()
    {
        return $this->hasOne(Transaksi::class, 'reversal_of_id');
    }

    public function isVoided(): bool
    {
        return ! $this->is_reversal && $this->reversalEntry()->exists();
    }
}
