@extends('layouts.app')

@section('title', 'Transaksi Kolektif')
@section('page_title', 'Catat Transaksi Kolektif')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-school"></i> Pilih Kelas</h2>
    </div>
    <div class="card-body">
        <form action="{{ route('transaksi.kolektif.form') }}" method="GET" id="kelasSelectForm">
            <div class="form-group" style="margin-bottom: 0; max-width: 400px;">
                <label for="kelas_id" class="form-label">Kelas Sasaran</label>
                <select name="kelas_id" id="kelas_id" class="form-control" onchange="document.getElementById('kelasSelectForm').submit();" required>
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($kelasScope as $k)
                        <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>
                            {{ $k->nama_kelas }} (Ajaran {{ $k->tahun_ajaran }})
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

@if($selectedKelas)
    @if(session('info'))
        <div class="alert alert-info" style="margin-bottom: 20px;">
            <i class="fa-solid fa-circle-info"></i> {{ session('info') }}
        </div>
    @endif
    @if($errors->has('error_kolektif'))
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <i class="fa-solid fa-circle-xmark"></i> {{ $errors->first('error_kolektif') }}
        </div>
    @endif

    <form action="{{ route('transaksi.kolektif.store') }}" method="POST" id="kolektifStoreForm">
        @csrf
        <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">

        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-sliders"></i> Pengaturan Transaksi Kelas - {{ $selectedKelas->nama_kelas }}</h2>
            </div>
            <div class="card-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <!-- Transaction Type -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Tipe Transaksi</label>
                    <div style="display: flex; gap: 16px; margin-top: 8px; flex-wrap: wrap;">
                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; white-space: nowrap;">
                            <input type="radio" name="tipe" value="setor" {{ old('tipe', 'setor') === 'setor' ? 'checked' : '' }} onchange="updateTipeIndicator()" style="accent-color: var(--success); width: 18px; height: 18px; margin: 0;">
                            <span style="color: var(--success);"><i class="fa-solid fa-circle-arrow-down"></i> Setoran</span>
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; white-space: nowrap;">
                            <input type="radio" name="tipe" value="tarik" {{ old('tipe') === 'tarik' ? 'checked' : '' }} onchange="updateTipeIndicator()" style="accent-color: var(--danger); width: 18px; height: 18px; margin: 0;">
                            <span style="color: var(--danger);"><i class="fa-solid fa-circle-arrow-up"></i> Penarikan</span>
                        </label>
                    </div>
                </div>

                <!-- Date -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="tanggal" class="form-label">Tanggal</label>
                    <input type="date" id="tanggal" name="tanggal" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                </div>

                <!-- Default Remarks -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="keterangan_default" class="form-label">Keterangan Default</label>
                    <input type="text" id="keterangan_default" name="keterangan_default" class="form-control" placeholder="Contoh: Setoran Harian" value="{{ old('keterangan_default', 'Setoran Harian') }}">
                </div>

                <!-- Default Amount Helper -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="nominal_default" class="form-label">Isi Nominal Ke Semua (Alat Bantu)</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <span style="position: absolute; left: 14px; font-weight: 700; color: var(--slate); line-height: 1;">Rp</span>
                        <input type="number" id="nominal_default" class="form-control" placeholder="Nominal..." style="padding-left: 42px; border-color: var(--secondary);" oninput="applyDefaultNominal()">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-users"></i> Daftar Siswa (Jumlah Siswa: {{ count($siswas) }})</h2>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="clearAllAmounts()"><i class="fa-solid fa-eraser"></i> Kosongkan Nominal</button>
                </div>
            </div>
            <div class="card-body">
                @if(count($siswas) > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>

                                    <th style="width: 30%;">Nama Siswa</th>
                                    <th style="width: 20%;" class="text-right">Saldo Saat Ini</th>
                                    <th style="width: 15%;" class="text-center"><span class="tipe-label-icon" style="color: var(--success);"><i class="fa-solid fa-circle-arrow-down"></i></span> Nominal (Rp)</th>
                                    <th style="width: 20%;">Keterangan Khusus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($siswas as $idx => $s)
                                    <tr>

                                        <td>{{ $s->nama }}</td>
                                        <td class="text-right">
                                            <strong style="color: var(--primary);">Rp {{ number_format($s->saldo, 0, ',', '.') }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <input type="hidden" name="transaksi[{{ $idx }}][siswa_id]" value="{{ $s->id }}">
                                            <input type="number" 
                                                   name="transaksi[{{ $idx }}][jumlah]" 
                                                   class="form-control input-jumlah" 
                                                   placeholder="0" 
                                                   min="0" 
                                                   style="text-align: right;" 
                                                   value="{{ old("transaksi.{$idx}.jumlah") }}"
                                                   tabindex="{{ $idx + 1 }}">
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="transaksi[{{ $idx }}][keterangan]" 
                                                   class="form-control" 
                                                   placeholder="Bawaan" 
                                                   value="{{ old("transaksi.{$idx}.keterangan") }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 30px;"><i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi Kolektif</button>
                    </div>
                @else
                    <p class="text-center" style="padding: 40px; color: var(--slate);">Belum ada siswa di kelas ini.</p>
                @endif
            </div>
        </div>
    </form>
@endif
@endsection

@section('scripts')
<script>
    function applyDefaultNominal() {
        const nominalDefault = document.getElementById('nominal_default').value;
        const inputs = document.querySelectorAll('.input-jumlah');
        inputs.forEach(input => {
            input.value = nominalDefault;
        });
    }

    function clearAllAmounts() {
        const inputs = document.querySelectorAll('.input-jumlah');
        inputs.forEach(input => {
            input.value = '';
        });
        document.getElementById('nominal_default').value = '';
    }

    function updateTipeIndicator() {
        const tipeVal = document.querySelector('input[name="tipe"]:checked').value;
        const icons = document.querySelectorAll('.tipe-label-icon');
        const defaultRemarks = document.getElementById('keterangan_default');
        
        if (tipeVal === 'setor') {
            icons.forEach(el => {
                el.innerHTML = '<i class="fa-solid fa-circle-arrow-down"></i>';
                el.style.color = 'var(--success)';
            });
            if (defaultRemarks.value === 'Penarikan Harian') {
                defaultRemarks.value = 'Setoran Harian';
            }
        } else {
            icons.forEach(el => {
                el.innerHTML = '<i class="fa-solid fa-circle-arrow-up"></i>';
                el.style.color = 'var(--danger)';
            });
            if (defaultRemarks.value === 'Setoran Harian') {
                defaultRemarks.value = 'Penarikan Harian';
            }
        }
    }

    // Run once on load to establish state
    window.addEventListener('DOMContentLoaded', (event) => {
        if (document.querySelector('input[name="tipe"]')) {
            updateTipeIndicator();
        }
    });
</script>
@endsection
