@extends('layouts.app')

@section('title', 'Manajemen Guru')
@section('page_title', 'Kelola Guru')

@section('content')
@php
    $editGuru = null;
    if (request()->filled('edit')) {
        $editGuru = $guruList->firstWhere('id', request('edit'));
    }
@endphp

<div class="dashboard-grid">
    <!-- Left Column: Guru List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-users-rectangle"></i> Daftar Guru</h2>
        </div>
        <div class="card-body">
            @if(session('delete_error') || $errors->has('delete_error'))
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-xmark"></i> {{ session('delete_error') ?: $errors->first('delete_error') }}
                </div>
            @endif

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Guru</th>
                            <th>Email</th>
                            <th>Kelas Diampu</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($guruList as $g)
                            <tr>
                                <td><strong>{{ $g->name }}</strong></td>
                                <td>{{ $g->email }}</td>
                                <td>
                                    @if($g->kelasDiampu->count() > 0)
                                        <span class="badge badge-success">
                                            Wali Kelas: {{ $g->kelasDiampu->pluck('nama_kelas')->implode(', ') }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary" style="color: var(--slate); font-style: italic;">
                                            Tidak mengampu kelas
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-center gap-2" style="justify-content: center;">
                                        <a href="{{ route('guru.index', ['edit' => $g->id]) }}" class="btn btn-secondary btn-sm" title="Edit Guru">
                                            <i class="fa-solid fa-user-pen"></i> Edit
                                        </a>
                                        <form action="{{ route('guru.destroy', $g->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun guru ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus Guru">
                                                <i class="fa-solid fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center" style="padding: 30px; color: var(--slate);">Belum ada data guru terdaftar.</td>
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
                @if($editGuru)
                    <i class="fa-solid fa-user-pen"></i> Edit Akun Guru
                @else
                    <i class="fa-solid fa-user-plus"></i> Tambah Guru Baru
                @endif
            </h2>
            @if($editGuru)
                <a href="{{ route('guru.index') }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-xmark"></i> Batal</a>
            @endif
        </div>
        <div class="card-body">
            <form action="{{ $editGuru ? route('guru.update', $editGuru->id) : route('guru.store') }}" method="POST">
                @csrf
                @if($editGuru)
                    @method('PUT')
                @endif

                <!-- Name Field -->
                <div class="form-group">
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: Ahmad Subardjo, S.Pd." value="{{ old('name', $editGuru ? $editGuru->name : '') }}" required>
                    @error('name')
                        <small style="color: var(--danger);">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Email Field -->
                <div class="form-group">
                    <label for="email" class="form-label">Alamat Email</label>
                    <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Contoh: ahmad@tabungan.com" value="{{ old('email', $editGuru ? $editGuru->email : '') }}" required>
                    @error('email')
                        <small style="color: var(--danger);">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        Password
                        @if($editGuru)
                            <small style="font-weight: 400; color: var(--slate); font-style: italic;">(Kosongkan jika tidak ingin diubah)</small>
                        @endif
                    </label>
                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" {{ $editGuru ? '' : 'required' }}>
                    @error('password')
                        <small style="color: var(--danger);">{{ $message }}</small>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px;">
                    <i class="fa-solid fa-floppy-disk"></i> {{ $editGuru ? 'Simpan Perubahan' : 'Daftarkan Akun Guru' }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
