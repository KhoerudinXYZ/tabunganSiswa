@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard Ringkasan')

@section('content')
<!-- Welcome Banner Premium -->
<div class="premium-banner">
    <div class="banner-title">Selamat datang kembali, {{ explode(' ', auth()->user()->name)[0] }}! 👋</div>
    <div class="banner-subtitle">Berikut adalah ringkasan aktivitas tabungan siswa hari ini.</div>
</div>

<div class="stats-grid">
    <!-- Card 1: Total Saldo -->
    <div class="stat-card premium" style="border-left-color: var(--primary);">
        <div class="stat-info">
            <span class="stat-label">Total Saldo Kas Tabungan</span>
            <span class="stat-value">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</span>
        </div>
        <div class="stat-icon primary">
            <i class="fa-solid fa-wallet"></i>
        </div>
    </div>

    <!-- Card 2: Total Siswa -->
    <div class="stat-card premium" style="border-left-color: #4f46e5;">
        <div class="stat-info">
            <span class="stat-label">Total Siswa Terdaftar</span>
            <span class="stat-value">{{ $totalSiswa }} Anak</span>
        </div>
        <div class="stat-icon secondary">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>

    <!-- Card 3: Akumulasi Setoran -->
    <div class="stat-card premium" style="border-left-color: var(--success);">
        <div class="stat-info">
            <span class="stat-label">Total Uang Masuk (Setor)</span>
            <span class="stat-value" style="color: var(--success);">Rp {{ number_format($totalSetor, 0, ',', '.') }}</span>
        </div>
        <div class="stat-icon success">
            <i class="fa-solid fa-circle-arrow-down"></i>
        </div>
    </div>

    <!-- Card 4: Akumulasi Penarikan -->
    <div class="stat-card premium" style="border-left-color: var(--danger);">
        <div class="stat-info">
            <span class="stat-label">Total Uang Keluar (Tarik)</span>
            <span class="stat-value" style="color: var(--danger);">Rp {{ number_format($totalTarik, 0, ',', '.') }}</span>
        </div>
        <div class="stat-icon danger">
            <i class="fa-solid fa-circle-arrow-up"></i>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Latest Transactions widget -->
    <div class="card" style="border: none; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);">
        <div class="card-header" style="background: rgba(248, 250, 252, 0.8); backdrop-filter: blur(8px);">
            <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i> 5 Transaksi Terakhir</h2>
            <a href="{{ route('transaksi.index') }}" class="btn btn-secondary btn-sm" style="border-radius: 20px;">Lihat Semua</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" style="border-collapse: separate; border-spacing: 0 8px;">
                    <thead>
                        <tr>
                            <th style="border: none; background: transparent; padding-bottom: 0;">Tanggal</th>
                            <th style="border: none; background: transparent; padding-bottom: 0;">Siswa</th>
                            <th style="border: none; background: transparent; padding-bottom: 0;">Tipe</th>
                            <th class="text-right" style="border: none; background: transparent; padding-bottom: 0;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestTransactions as $tx)
                            <tr style="background: var(--light-bg); transition: all 0.2s ease;">
                                <td style="border: none; border-radius: 12px 0 0 12px;">
                                    <span style="font-weight: 600; color: var(--slate);">{{ $tx->tanggal->format('d M Y') }}</span>
                                </td>
                                <td style="border: none;">
                                    <strong>{{ $tx->siswa->nama }}</strong><br>
                                    <small style="color: var(--slate);">Oleh: {{ $tx->petugas->name }}</small>
                                </td>
                                <td style="border: none;">
                                    @if($tx->tipe === 'setor')
                                        <span class="modern-badge success"><i class="fa-solid fa-arrow-down"></i> Setor</span>
                                    @else
                                        <span class="modern-badge danger"><i class="fa-solid fa-arrow-up"></i> Tarik</span>
                                    @endif
                                </td>
                                <td class="text-right" style="border: none; border-radius: 0 12px 12px 0;">
                                    <strong style="font-size: 15px; color: {{ $tx->tipe === 'setor' ? 'var(--success)' : 'var(--danger)' }};">
                                        {{ $tx->tipe === 'setor' ? '+' : '-' }} Rp {{ number_format($tx->jumlah, 0, ',', '.') }}
                                    </strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center" style="padding: 40px 20px; color: var(--slate); border: none;">
                                    <div style="font-size: 40px; color: #cbd5e1; margin-bottom: 16px;"><i class="fa-solid fa-folder-open"></i></div>
                                    Belum ada transaksi saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kelas Summary widget -->
    <div class="card" style="border: none; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);">
        <div class="card-header" style="background: rgba(248, 250, 252, 0.8); backdrop-filter: blur(8px);">
            <h2 class="card-title"><i class="fa-solid fa-chart-pie" style="color: var(--secondary);"></i> Ringkasan per Kelas</h2>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('kelas.index') }}" class="btn btn-secondary btn-sm" style="border-radius: 20px;">Kelola</a>
            @endif
        </div>
        <div class="card-body">
            @php
                $maxTabungan = $classes->max('total_tabungan') ?: 1;
            @endphp
            <div style="display: flex; flex-direction: column; gap: 20px;">
                @forelse($classes as $c)
                    @php
                        $classTabungan = (float) ($c->total_tabungan ?? 0);
                        $percentage = $maxTabungan > 0 ? min(100, round(($classTabungan / $maxTabungan) * 100)) : 0;
                    @endphp
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <strong style="font-size: 14px;">{{ $c->nama_kelas }}</strong>
                            <strong style="font-size: 14px; color: var(--primary);">Rp {{ number_format($c->total_tabungan ?? 0, 0, ',', '.') }}</strong>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: {{ $percentage }}%;"></div>
                        </div>
                        <div style="font-size: 12px; color: var(--slate); margin-top: 4px; text-align: right;">
                            {{ $c->siswas_count }} Siswa Aktif
                        </div>
                    </div>
                @empty
                    <div class="text-center" style="padding: 30px; color: var(--slate);">
                        <i class="fa-solid fa-school-circle-xmark" style="font-size: 32px; color: #cbd5e1; margin-bottom: 12px;"></i><br>
                        Belum ada data kelas.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
