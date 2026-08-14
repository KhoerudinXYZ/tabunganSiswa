@extends('layouts.app')

@section('title', 'Manajemen Akun')
@section('page_title', 'Pengaturan Akun')

@section('content')
<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <h2 class="card-title">Profil Pengguna</h2>
    </div>
    
    <div class="card-body">
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <div style="color: var(--danger); font-size: 13px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <div style="color: var(--danger); font-size: 13px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">
            
            <h3 style="font-size: 15px; font-weight: 600; margin-bottom: 12px; color: var(--text);">Ubah Kata Sandi (Opsional)</h3>
            <p style="font-size: 13px; color: var(--slate); margin-bottom: 15px;">Biarkan kosong jika tidak ingin mengubah kata sandi Anda.</p>

            <div class="form-group">
                <label for="password" class="form-label">Kata Sandi Baru</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 8 karakter">
                @error('password')
                    <div style="color: var(--danger); font-size: 13px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi Baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Ketik ulang kata sandi baru">
            </div>

            <div class="d-flex" style="justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
