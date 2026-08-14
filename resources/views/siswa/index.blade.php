@extends('layouts.app')

@section('title', 'Manajemen Siswa')
@section('page_title', 'Data Siswa')

@section('content')
<div class="card mb-4">
    <!-- Filter and Search Row -->
    <div class="card-body">
        <form action="{{ route('siswa.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 2; min-width: 200px;">
                <label for="search" class="form-label">Cari Nama</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Ketik nama siswa..." value="{{ request('search') }}">
            </div>

            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                <label for="kelas_id" class="form-label">Filter Kelas</label>
                <select id="kelas_id" name="kelas_id" class="form-control">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('kelas_id') == $c->id ? 'selected' : '' }}>{{ $c->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0; display: flex; gap: 8px; flex: 1; min-width: 200px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fa-solid fa-magnifying-glass"></i> Cari
                </button>
                <a href="{{ route('siswa.index') }}" class="btn btn-secondary" style="flex: 1; text-align: center;">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-graduation-cap"></i> Daftar Siswa Terdaftar</h2>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('siswa.import.form') }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-import"></i> Import Siswa</a>
            <a href="{{ route('siswa.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-circle-plus"></i> Tambah Siswa</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>

                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Jenis Kelamin</th>
                        <th class="text-right">Saldo Saat Ini</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswas as $s)
                        <tr>

                            <td>
                                <strong>{{ $s->nama }}</strong><br>
                                <small style="color: var(--slate);"><i class="fa-solid fa-location-dot"></i> {{ $s->alamat ?? 'Alamat belum diisi' }}</small>
                            </td>
                            <td>
                                @if($s->kelas)
                                    <span class="badge badge-info">{{ $s->kelas->nama_kelas }}</span>
                                @else
                                    <span class="badge badge-secondary">Belum ada kelas</span>
                                @endif
                            </td>
                            <td>{{ $s->jenis_kelamin === 'L' ? 'Laki-laki' : ($s->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</td>
                            <td class="text-right">
                                <strong style="font-size: 15px; color: var(--primary);">Rp {{ number_format($s->saldo, 0, ',', '.') }}</strong>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-center gap-2" style="justify-content: center;">
                                    <a href="{{ route('siswa.edit', $s->id) }}" class="btn btn-secondary btn-sm" title="Ubah Data Siswa">
                                        <i class="fa-solid fa-user-gear"></i> Edit
                                    </a>
                                    <form action="{{ route('siswa.destroy', $s->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data siswa ini? Semua riwayat transaksi siswa juga akan dihapus!')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus Siswa">
                                            <i class="fa-solid fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 40px; color: var(--slate);">Siswa tidak ditemukan atau belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Laravel default pagination rendering -->
        <div class="mt-4">
            {{ $siswas->links() }}
        </div>
    </div>
</div>
@endsection
