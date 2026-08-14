@extends('layouts.app')

@section('title', 'Kelas Saya')
@section('page_title', 'Kelas Saya')

@section('content')
@forelse($classes as $c)
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-school"></i> {{ $c->nama_kelas }} <small style="font-weight: 400; color: var(--slate);">(Ajaran {{ $c->tahun_ajaran }})</small></h2>
            <a href="{{ route('transaksi.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-file-invoice-dollar"></i> Catat Transaksi</a>
        </div>
        <div class="card-body">
            <div class="stats-grid" style="margin-bottom: 20px;">
                <div class="stat-card">
                    <div class="stat-info">
                        <span class="stat-label">Jumlah Siswa</span>
                        <span class="stat-value">{{ $c->siswas_count }} Anak</span>
                    </div>
                    <div class="stat-icon secondary">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <span class="stat-label">Total Tabungan Kelas</span>
                        <span class="stat-value">Rp {{ number_format($c->total_tabungan ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>

                            <th>Nama Siswa</th>
                            <th>Jenis Kelamin</th>
                            <th class="text-right">Saldo Saat Ini</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($c->siswas as $s)
                            <tr>

                                <td>{{ $s->nama }}</td>
                                <td>{{ $s->jenis_kelamin === 'L' ? 'Laki-laki' : ($s->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</td>
                                <td class="text-right">
                                    <strong style="color: var(--primary);">Rp {{ number_format($s->saldo, 0, ',', '.') }}</strong>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-center gap-2" style="justify-content: center;">
                                        <a href="{{ route('siswa.edit', $s->id) }}" class="btn btn-secondary btn-sm" title="Ubah Data Siswa">
                                            <i class="fa-solid fa-user-gear"></i>
                                        </a>
                                        <a href="{{ route('transaksi.create', ['siswa_id' => $s->id]) }}" class="btn btn-primary btn-sm" title="Catat Transaksi">
                                            <i class="fa-solid fa-file-invoice-dollar"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center" style="padding: 30px; color: var(--slate);">Belum ada data siswa di kelas ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body">
            <p class="text-center" style="padding: 30px; color: var(--slate);">
                Anda belum ditunjuk sebagai wali kelas untuk kelas mana pun. Silakan hubungi Administrator.
            </p>
        </div>
    </div>
@endforelse
@endsection
