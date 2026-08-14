<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Data grouping for kelas: admin sees every class, guru sees only the
        // class(es) they are homeroom teacher of.
        $classesQuery = Kelas::withCount('siswas')->withSum('siswas as total_tabungan', 'saldo');
        if (! $user->isAdmin()) {
            $classesQuery->where('wali_kelas_id', $user->id);
        }
        $classes = $classesQuery->get();
        $kelasIds = $classes->pluck('id');

        if ($user->isAdmin()) {
            $totalSiswa = Siswa::count();
            $totalSaldo = Siswa::sum('saldo');
            $transaksiScope = Transaksi::query();
        } else {
            $totalSiswa = Siswa::whereIn('kelas_id', $kelasIds)->count();
            $totalSaldo = Siswa::whereIn('kelas_id', $kelasIds)->sum('saldo');
            $transaksiScope = Transaksi::whereHas('siswa', function ($q) use ($kelasIds) {
                $q->whereIn('kelas_id', $kelasIds);
            });
        }
        $totalSetor = (clone $transaksiScope)->where('tipe', 'setor')->sum('jumlah');
        $totalTarik = (clone $transaksiScope)->where('tipe', 'tarik')->sum('jumlah');

        $latestTransactions = (clone $transaksiScope)
            ->with(['siswa', 'petugas'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'totalSiswa',
            'totalSaldo',
            'totalSetor',
            'totalTarik',
            'latestTransactions',
            'classes'
        ));
    }


}
