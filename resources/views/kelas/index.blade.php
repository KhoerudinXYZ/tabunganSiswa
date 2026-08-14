@extends('layouts.app')

@section('title', 'Manajemen Kelas')
@section('page_title', 'Manajemen Kelas')

@section('content')
@php
    $editKelas = null;
    if (request()->filled('edit')) {
        $editKelas = $classes->firstWhere('id', request('edit'));
    }
@endphp

<div class="dashboard-grid">
    <!-- Left Column: Kelas List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-list"></i> Daftar Kelas</h2>
        </div>
        <div class="card-body">
            @if(session('delete_error') || $errors->has('delete_error'))
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-xmark"></i> {{ session('delete_error') ?: $errors->first('delete_error') }}
                </div>
            @endif

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Kelas</th>
                            <th>Tahun Ajaran</th>
                            <th>Wali Kelas</th>
                            <th class="text-center">Jumlah Siswa</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $c)
                            <tr>
                                <td><strong>{{ $c->nama_kelas }}</strong></td>
                                <td>{{ $c->tahun_ajaran }}</td>
                                <td>
                                    @if($c->waliKelas)
                                        {{ $c->waliKelas->name }}
                                    @else
                                        <span style="color: var(--slate); font-style: italic;">Belum ditentukan</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info">{{ $c->siswas_count }} Siswa</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-center gap-2" style="justify-content: center;">
                                        <a href="{{ route('kelas.index', ['edit' => $c->id]) }}" class="btn btn-secondary btn-sm" title="Edit Kelas">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <form action="{{ route('kelas.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus Kelas">
                                                <i class="fa-solid fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center" style="padding: 30px; color: var(--slate);">Belum ada kelas terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Form (Create or Edit) -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                @if($editKelas)
                    <i class="fa-solid fa-pen-to-square"></i> Edit Kelas
                @else
                    <i class="fa-solid fa-circle-plus"></i> Tambah Kelas Baru
                @endif
            </h2>
            @if($editKelas)
                <a href="{{ route('kelas.index') }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-xmark"></i> Batal</a>
            @endif
        </div>
        <div class="card-body">
            <form action="{{ $editKelas ? route('kelas.update', $editKelas->id) : route('kelas.store') }}" method="POST">
                @csrf
                @if($editKelas)
                    @method('PUT')
                @endif

                <div class="form-group">
                    <label for="nama_kelas" class="form-label">Nama Kelas</label>
                    <input type="text" id="nama_kelas" name="nama_kelas" class="form-control" placeholder="Contoh: Kelas 1-A" value="{{ old('nama_kelas', $editKelas ? $editKelas->nama_kelas : '') }}" required>
                </div>

                <div class="form-group">
                    <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                    <input type="text" id="tahun_ajaran" name="tahun_ajaran" class="form-control" placeholder="Contoh: 2025/2026" value="{{ old('tahun_ajaran', $editKelas ? $editKelas->tahun_ajaran : '') }}" required>
                </div>

                <div class="form-group">
                    <label for="wali_kelas_id" class="form-label">Wali Kelas</label>
                    <select id="wali_kelas_id" name="wali_kelas_id" class="form-control">
                        <option value="">-- Belum Ditentukan --</option>
                        @foreach($guruList as $g)
                            <option value="{{ $g->id }}" {{ old('wali_kelas_id', $editKelas ? $editKelas->wali_kelas_id : '') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <small style="color: var(--slate); display: block; margin-top: 4px;">
                        Guru yang ditunjuk hanya dapat mengelola data siswa &amp; transaksi di kelas ini.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    @if($editKelas)
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    @else
                        <i class="fa-solid fa-plus"></i> Tambah Kelas
                    @endif
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
