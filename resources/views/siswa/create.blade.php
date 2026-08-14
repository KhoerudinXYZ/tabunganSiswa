@extends('layouts.app')

@section('title', 'Tambah Siswa Baru')
@section('page_title', 'Tambah Siswa Baru')

@section('content')
<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-user-plus"></i> Formulir Pendaftaran Siswa</h2>
        <a href="{{ route('siswa.index') }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
    <div class="card-body">
        <form action="{{ route('siswa.store') }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">


                <div class="form-group">
                    <label for="nama" class="form-label">Nama Lengkap Siswa</label>
                    <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: Rian Hidayat" value="{{ old('nama') }}" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" class="form-control">
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L" {{ old('jenis_kelamin') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ old('jenis_kelamin') === 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="kelas_id" class="form-label">Kelas Siswa</label>
                    @if(auth()->user()->isGuru() && $classes->count() === 1)
                        <input type="text" class="form-control" value="{{ $classes->first()->nama_kelas }} (Ajaran: {{ $classes->first()->tahun_ajaran }})" disabled>
                        <input type="hidden" name="kelas_id" value="{{ $classes->first()->id }}">
                        <small style="color: var(--slate); display: block; margin-top: 4px;">Anda hanya dapat menambahkan siswa ke kelas yang Anda ampu.</small>
                    @else
                        <select id="kelas_id" name="kelas_id" class="form-control">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ old('kelas_id') == $c->id ? 'selected' : '' }}>{{ $c->nama_kelas }} (Ajaran: {{ $c->tahun_ajaran }})</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>



            <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-user-plus"></i> Simpan Data Siswa</button>
        </form>
    </div>
</div>
@endsection


