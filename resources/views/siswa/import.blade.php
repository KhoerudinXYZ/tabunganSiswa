@extends('layouts.app')

@section('title', 'Import Siswa')
@section('page_title', 'Import Data Siswa')

@section('content')
<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-file-import"></i> Import Siswa via Excel</h2>
        <a href="{{ route('siswa.index') }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
    <div class="card-body">
        <div class="alert alert-info" style="margin-bottom: 24px;">
            <h4 style="margin-bottom: 8px; font-weight: 700;"><i class="fa-solid fa-circle-info"></i> Petunjuk Pengisian Data</h4>
            <p style="font-size: 14px; margin-bottom: 8px;">
                Untuk mendaftarkan siswa secara massal, silakan ikuti petunjuk berikut:
            </p>
            <ol style="font-size: 14px; padding-left: 20px; margin-bottom: 12px;">
                <li>Unduh file template Excel dengan mengklik tombol <strong>Unduh Template Excel</strong> di bawah.</li>
                <li>Buka file tersebut di Microsoft Excel atau program spreadsheet lainnya.</li>
                <li>Isi baris data siswa sesuai kolom yang tersedia:
                    <ul>
                        <li><strong>nama</strong>: Nama Lengkap Siswa (<strong>Wajib</strong>, maks 100 karakter).</li>
                        <li><strong>jenis_kelamin</strong>: Isi <strong>L</strong> untuk Laki-laki atau <strong>P</strong> untuk Perempuan (Opsional, boleh dikosongkan).</li>
                        <li><strong>alamat</strong>: Alamat rumah siswa (Opsional, boleh dikosongkan).</li>

                    </ul>
                </li>
                <li>Simpan file sebagai format <strong>Excel Workbook (*.xlsx)</strong> atau <strong>Excel 97-2003 (*.xls)</strong>.</li>
            </ol>
            <a href="{{ route('siswa.import.template') }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-download"></i> Unduh Template Excel</a>
        </div>

        <form action="{{ route('siswa.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Class Selection -->
            <div class="form-group">
                <label for="kelas_id" class="form-label">Pilih Kelas Target</label>
                <select name="kelas_id" id="kelas_id" class="form-control">
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ old('kelas_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->nama_kelas }} (Ajaran {{ $c->tahun_ajaran }})
                        </option>
                    @endforeach
                </select>
                <small style="color: var(--slate); display: block; margin-top: 4px;">Data siswa yang diimpor akan dimasukkan ke dalam kelas terpilih.</small>
            </div>

            <!-- File Upload -->
            <div class="form-group">
                <label for="file_excel" class="form-label">Pilih File Excel</label>
                <input type="file" id="file_excel" name="file_excel" class="form-control" accept=".xlsx,.xls" required>
                <small style="color: var(--slate); display: block; margin-top: 4px;">Ukuran berkas maksimal 2MB dengan ekstensi .xlsx atau .xls.</small>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 24px;"><i class="fa-solid fa-cloud-arrow-up"></i> Mulai Proses Import</button>
        </form>
    </div>
</div>
@endsection
