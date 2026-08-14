@extends('layouts.app')

@section('title', 'Kwitansi Transaksi')
@section('page_title', 'Bukti Transaksi')

@section('content')
<div class="no-print" style="max-width: 500px; margin: 0 auto 20px auto; display: flex; justify-content: space-between; gap: 12px;">
    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali ke Riwayat</a>
    <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Cetak Kwitansi</button>
</div>

@if($transaksi->is_reversal)
    <div class="no-print alert alert-danger" style="max-width: 500px; margin: 0 auto 20px auto;">
        <i class="fa-solid fa-rotate-left"></i> Ini adalah entri koreksi/pembatalan dari
        <a href="{{ route('transaksi.show', $transaksi->reversalOf->id) }}">#TX-{{ str_pad($transaksi->reversalOf->id, 5, '0', STR_PAD_LEFT) }}</a>.
    </div>
@elseif($transaksi->reversalEntry)
    <div class="no-print alert alert-danger" style="max-width: 500px; margin: 0 auto 20px auto;">
        <i class="fa-solid fa-circle-xmark"></i> Transaksi ini telah dibatalkan/dikoreksi. Lihat entri koreksi
        <a href="{{ route('transaksi.show', $transaksi->reversalEntry->id) }}">#TX-{{ str_pad($transaksi->reversalEntry->id, 5, '0', STR_PAD_LEFT) }}</a>.
    </div>
@endif

<div class="kwitansi-box">
    <div class="kwitansi-header">
        <h2>Kwitansi Tabungan Siswa</h2>
        <p>SDN 4 RAMBATAN KULON</p>
        <p style="font-size: 10px; margin-top: 4px; font-weight: normal;">Kecamatan Lohbener, Kab. Indramayu</p>
    </div>

    <div class="kwitansi-row">
        <span class="label">No. Transaksi</span>
        <span class="val">#TX-{{ str_pad($transaksi->id, 5, '0', STR_PAD_LEFT) }}</span>
    </div>
    
    <div class="kwitansi-row">
        <span class="label">Tanggal</span>
        <span class="val">{{ $transaksi->tanggal->translatedFormat('d F Y') }}</span>
    </div>

    <div style="border-top: 1px dashed var(--border-color); margin: 12px 0;"></div>



    <div class="kwitansi-row">
        <span class="label">Nama Siswa</span>
        <span class="val">{{ $transaksi->siswa->nama }}</span>
    </div>

    <div class="kwitansi-row">
        <span class="label">Kelas</span>
        <span class="val">{{ $transaksi->siswa->kelas ? $transaksi->siswa->kelas->nama_kelas : 'Tanpa Kelas' }}</span>
    </div>

    <div class="kwitansi-row">
        <span class="label">Jenis Transaksi</span>
        <span class="val" style="color: {{ $transaksi->tipe === 'setor' ? 'var(--success)' : 'var(--danger)' }}; text-transform: uppercase;">
            {{ $transaksi->tipe === 'setor' ? 'Setoran (Uang Masuk)' : 'Penarikan (Uang Keluar)' }}
        </span>
    </div>

    <div class="kwitansi-amount">
        Rp {{ number_format($transaksi->jumlah, 0, ',', '.') }}
    </div>

    <div class="kwitansi-row" style="margin-top: 8px;">
        <span class="label">Keterangan</span>
        <span class="val" style="font-style: italic;">{{ $transaksi->keterangan ?? '-' }}</span>
    </div>

    <div class="kwitansi-row" style="margin-top: 8px;">
        <span class="label">Sisa Saldo Terkini</span>
        <span class="val" style="font-weight: 700; color: var(--primary);">Rp {{ number_format($transaksi->siswa->saldo, 0, ',', '.') }}</span>
    </div>

    <div class="kwitansi-footer">
        <div class="kwitansi-sign">
            <span>Orang Tua/Wali</span>
            <div class="kwitansi-sign-space"></div>
            <span>(....................................)</span>
        </div>
        <div class="kwitansi-sign">
            <span>Petugas Penerima</span>
            <div class="kwitansi-sign-space"></div>
            <strong>{{ $transaksi->petugas->name }}</strong>
        </div>
    </div>
</div>

<div class="no-print text-center" style="max-width: 500px; margin: 20px auto 0 auto;">
    <p style="font-size: 12px; color: var(--slate);"><i class="fa-solid fa-info-circle"></i> Kwitansi ini dicetak secara komputerisasi dan sah sebagai bukti keuangan sekolah.</p>
</div>

@can('void', $transaksi)
    <div class="no-print card" style="max-width: 500px; margin: 20px auto 0 auto;">
        <div class="card-header">
            <h2 class="card-title" style="color: var(--danger);"><i class="fa-solid fa-triangle-exclamation"></i> Salah Catat Transaksi?</h2>
        </div>
        <div class="card-body">
            <p style="font-size: 13px; color: var(--slate); margin-bottom: 12px;">
                Transaksi lama tidak akan diubah/dihapus. Sistem akan membuat entri koreksi (kebalikan) dan mengembalikan saldo siswa, demi menjaga jejak audit keuangan.
            </p>
            <button type="button" class="btn btn-danger btn-block" onclick="document.getElementById('void_form').style.display='block'; this.style.display='none';">
                <i class="fa-solid fa-rotate-left"></i> Batalkan / Koreksi Transaksi Ini
            </button>
            <form id="void_form" action="{{ route('transaksi.void', $transaksi->id) }}" method="POST" style="display: none; margin-top: 12px;">
                @csrf
                <div class="form-group">
                    <label for="alasan" class="form-label">Alasan Pembatalan</label>
                    <textarea id="alasan" name="alasan" class="form-control" rows="2" placeholder="Contoh: Salah input nominal, seharusnya Rp 10.000" required>{{ old('alasan') }}</textarea>
                </div>
                <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Yakin ingin membatalkan transaksi ini? Entri koreksi akan dibuat.')">
                    <i class="fa-solid fa-check"></i> Konfirmasi Pembatalan
                </button>
            </form>
        </div>
    </div>
@endcan
@endsection
