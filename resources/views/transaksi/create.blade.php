@extends('layouts.app')

@section('title', 'Catat Transaksi')
@section('page_title', 'Catat Transaksi Baru')

@section('content')
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-file-invoice-dollar"></i> Transaksi Baru</h2>
        <a href="{{ route('transaksi.index') }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
    <div class="card-body">
        <form action="{{ route('transaksi.store') }}" method="POST">
            @csrf

            <!-- Student Selection with balance indicator -->
            <div class="form-group">
                <label for="siswa_id" class="form-label">Pilih Siswa</label>
                <select id="siswa_id" name="siswa_id" class="form-control" onchange="updateBalanceIndicator()" required>
                    <option value="" data-saldo="0">-- Cari & Pilih Siswa --</option>
                    @foreach($siswas as $s)
                        <option value="{{ $s->id }}" data-saldo="{{ $s->saldo }}" {{ old('siswa_id', request('siswa_id')) == $s->id ? 'selected' : '' }}>
                            {{ $s->nama }} ({{ $s->kelas ? $s->kelas->nama_kelas : 'Tanpa Kelas' }})
                        </option>
                    @endforeach
                </select>
                <!-- Live balance indicator -->
                <div id="saldo_indicator" style="display: none; margin-top: 8px; font-size: 13px; font-weight: 600; color: var(--primary);">
                    <i class="fa-solid fa-wallet"></i> Saldo saat ini: <span id="saldo_val">Rp 0</span>
                </div>
            </div>

            <!-- Transaction Type -->
            <div class="form-group">
                <label class="form-label">Tipe Transaksi</label>
                <div style="display: flex; gap: 20px; margin-top: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                        <input type="radio" name="tipe" value="setor" {{ old('tipe', 'setor') === 'setor' ? 'checked' : '' }} onchange="checkTransactionType()" style="accent-color: var(--success); width: 18px; height: 18px;">
                        <span style="color: var(--success);"><i class="fa-solid fa-circle-arrow-down"></i> Setoran (Deposit)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                        <input type="radio" name="tipe" value="tarik" {{ old('tipe') === 'tarik' ? 'checked' : '' }} onchange="checkTransactionType()" style="accent-color: var(--danger); width: 18px; height: 18px;">
                        <span style="color: var(--danger);"><i class="fa-solid fa-circle-arrow-up"></i> Penarikan (Withdrawal)</span>
                    </label>
                </div>
            </div>

            <!-- Amount -->
            <div class="form-group">
                <label for="jumlah" class="form-label">Jumlah Uang (Rupiah)</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <span style="position: absolute; left: 16px; font-weight: 700; color: var(--slate);">Rp</span>
                    <input type="number" id="jumlah" name="jumlah" class="form-control" placeholder="Contoh: 10000" style="padding-left: 45px;" value="{{ old('jumlah') }}" min="100" required>
                </div>
                <small style="color: var(--slate); display: block; margin-top: 4px;">Minimal Rp 100.</small>
            </div>

            <!-- Date -->
            <div class="form-group">
                <label for="tanggal" class="form-label">Tanggal Transaksi</label>
                <input type="date" id="tanggal" name="tanggal" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
            </div>

            <!-- Remarks -->
            <div class="form-group">
                <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                <input type="text" id="keterangan" name="keterangan" class="form-control" placeholder="Contoh: Uang saku sisa, membeli pensil, dll." value="{{ old('keterangan') }}">
            </div>

            <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-floppy-disk"></i> Catat & Simpan Transaksi</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function updateBalanceIndicator() {
        const select = document.getElementById('siswa_id');
        const selectedOption = select.options[select.selectedIndex];
        const saldo = parseFloat(selectedOption.getAttribute('data-saldo') || 0);

        const indicator = document.getElementById('saldo_indicator');
        const saldoVal = document.getElementById('saldo_val');

        if (select.value) {
            indicator.style.display = 'block';
            saldoVal.textContent = 'Rp ' + saldo.toLocaleString('id-ID');
        } else {
            indicator.style.display = 'none';
        }
    }

    function checkTransactionType() {
        // Just triggers live updates if any validation needs to be shown on client side
    }

    // Run once on load to show balance if redirected back with errors
    window.addEventListener('DOMContentLoaded', (event) => {
        updateBalanceIndicator();
    });
</script>
@endsection
