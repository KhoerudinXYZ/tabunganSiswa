@extends('layouts.app')

@section('title', 'Riwayat Transaksi')
@section('page_title', 'Transaksi Tabungan')

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('transaksi.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label for="search" class="form-label">Cari Nama</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Cari siswa..." value="{{ request('search') }}">
            </div>

            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 140px;">
                <label for="tipe" class="form-label">Tipe Transaksi</label>
                <select id="tipe" name="tipe" class="form-control">
                    <option value="">Semua Tipe</option>
                    <option value="setor" {{ request('tipe') === 'setor' ? 'selected' : '' }}>Setoran (+)</option>
                    <option value="tarik" {{ request('tipe') === 'tarik' ? 'selected' : '' }}>Penarikan (-)</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 140px;">
                <label for="tanggal_mulai" class="form-label">Dari Tanggal</label>
                <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" value="{{ request('tanggal_mulai') }}">
            </div>

            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 140px;">
                <label for="tanggal_selesai" class="form-label">Sampai Tanggal</label>
                <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="form-control" value="{{ request('tanggal_selesai') }}">
            </div>

            <div class="form-group" style="margin-bottom: 0; display: flex; gap: 8px; flex: 1; min-width: 200px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="{{ route('transaksi.index') }}" class="btn btn-secondary" style="flex: 1; text-align: center;">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-receipt"></i> Mutasi Rekening Tabungan</h2>
        <a href="{{ route('transaksi.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-file-invoice-dollar"></i> Catat Transaksi Baru</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Tanggal</th>

                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th class="text-right">Nominal</th>
                        <th>Petugas</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksis as $tx)
                        <tr>
                            <td>#TX-{{ str_pad($tx->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $tx->tanggal->format('d/m/Y') }}</td>

                            <td>{{ $tx->siswa->nama }}</td>
                            <td><span class="badge badge-info">{{ $tx->siswa->kelas ? $tx->siswa->kelas->nama_kelas : 'Tanpa Kelas' }}</span></td>
                            <td>
                                @if($tx->tipe === 'setor')
                                    <span class="badge badge-success">Setoran</span>
                                @else
                                    <span class="badge badge-danger">Penarikan</span>
                                @endif
                            </td>
                            <td>
                                @if($tx->is_reversal)
                                    <span class="badge badge-danger" title="Koreksi dari #TX-{{ str_pad($tx->reversal_of_id, 5, '0', STR_PAD_LEFT) }}">Koreksi</span>
                                @elseif($tx->reversalEntry)
                                    <span class="badge badge-danger" title="Dibatalkan lewat #TX-{{ str_pad($tx->reversalEntry->id, 5, '0', STR_PAD_LEFT) }}">Dibatalkan</span>
                                @else
                                    <span class="badge badge-success">Sah</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <strong style="color: {{ $tx->tipe === 'setor' ? 'var(--success)' : 'var(--danger)' }}">
                                    {{ $tx->tipe === 'setor' ? '+' : '-' }} Rp {{ number_format($tx->jumlah, 0, ',', '.') }}
                                </strong>
                            </td>
                            <td>{{ $tx->petugas->name }}</td>
                            <td class="text-center">
                                <a href="{{ route('transaksi.show', $tx->id) }}" class="btn btn-secondary btn-sm" title="Lihat Kwitansi / Cetak">
                                    <i class="fa-solid fa-print"></i> Kwitansi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 40px; color: var(--slate);">Belum ada riwayat transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $transaksis->links() }}
        </div>
    </div>
</div>
@endsection
