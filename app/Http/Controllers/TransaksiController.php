<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientSaldoException;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Transaksi::class);

        $kelasIds = $this->kelasScope()->pluck('id');

        $query = Transaksi::with(['siswa.kelas', 'petugas', 'reversalOf', 'reversalEntry']);

        if (!Auth::user()->isAdmin()) {
            $query->whereHas('siswa', function ($q) use ($kelasIds) {
                $q->whereIn('kelas_id', $kelasIds);
            });
        }

        // Filter search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('siswa', function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        // Filter tipe
        if ($request->filled('tipe')) {
            $query->where('tipe', $request->input('tipe'));
        }

        // Filter tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->input('tanggal_selesai'));
        }

        $transaksis = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return view('transaksi.index', compact('transaksis'));
    }

    public function create()
    {
        $this->authorize('create', Transaksi::class);

        $kelasIds = $this->kelasScope()->pluck('id');
        $siswas = Siswa::with('kelas')->whereIn('kelas_id', $kelasIds)->orderBy('nama', 'asc')->get();

        return view('transaksi.create', compact('siswas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'tipe' => 'required|in:setor,tarik',
            'jumlah' => 'required|numeric|min:100',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $siswa = Siswa::findOrFail($request->input('siswa_id'));
        $this->authorize('create', [Transaksi::class, $siswa]);

        $tipe = $request->input('tipe');
        $jumlah = $request->input('jumlah');

        try {
            $transaksi = DB::transaction(function () use ($siswa, $tipe, $jumlah, $request) {
                // Lock the student row so a concurrent transaction can't read a
                // stale balance and push saldo negative (check-then-act race).
                $locked = Siswa::whereKey($siswa->id)->lockForUpdate()->first();

                if ($tipe === 'tarik' && $locked->saldo < $jumlah) {
                    throw new InsufficientSaldoException((float) $locked->saldo);
                }

                if ($tipe === 'setor') {
                    $locked->increment('saldo', $jumlah);
                } else {
                    $locked->decrement('saldo', $jumlah);
                }

                return Transaksi::create([
                    'siswa_id' => $locked->id,
                    'user_id' => Auth::id(), // recorded by currently logged in user
                    'tipe' => $tipe,
                    'jumlah' => $jumlah,
                    'tanggal' => $request->input('tanggal'),
                    'keterangan' => $request->input('keterangan'),
                ]);
            });
        } catch (InsufficientSaldoException $e) {
            return back()->withErrors([
                'jumlah' => "Saldo siswa tidak mencukupi untuk melakukan penarikan. Saldo saat ini: Rp " . number_format($e->saldoTersedia, 0, ',', '.')
            ])->withInput();
        }

        return redirect()->route('transaksi.show', $transaksi->id)
            ->with('success', 'Transaksi berhasil dicatat. Silakan cetak kwitansi di bawah.');
    }

    public function show(Transaksi $transaksi)
    {
        $transaksi->load(['siswa.kelas', 'petugas', 'reversalOf', 'reversalEntry']);
        $this->authorize('view', $transaksi);

        return view('transaksi.show', compact('transaksi'));
    }

    /**
     * Show the collective transaction form.
     */
    public function kolektifForm(Request $request)
    {
        $this->authorize('create', Transaksi::class);

        $kelasScope = $this->kelasScope();
        $kelasId = $request->input('kelas_id');

        // If no class is selected but there is only one class in scope, select it automatically
        if (!$kelasId && $kelasScope->count() === 1) {
            $kelasId = $kelasScope->first()->id;
        }

        $siswas = [];
        $selectedKelas = null;
        if ($kelasId) {
            $selectedKelas = $kelasScope->firstWhere('id', $kelasId);
            if ($selectedKelas) {
                $siswas = Siswa::where('kelas_id', $kelasId)->orderBy('nama', 'asc')->get();
            } else {
                abort(403);
            }
        }

        return view('transaksi.kolektif', [
            'kelasScope' => $kelasScope,
            'selectedKelas' => $selectedKelas,
            'siswas' => $siswas,
            'kelasId' => $kelasId,
        ]);
    }

    /**
     * Store collective transactions.
     */
    public function kolektifStore(Request $request)
    {
        $this->authorize('create', Transaksi::class);

        $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'tipe' => 'required|in:setor,tarik',
            'tanggal' => 'required|date',
            'keterangan_default' => 'nullable|string|max:255',
            'transaksi' => 'required|array',
            'transaksi.*.siswa_id' => 'required|exists:siswas,id',
            'transaksi.*.jumlah' => 'nullable|numeric|min:0',
            'transaksi.*.keterangan' => 'nullable|string|max:255',
        ]);

        $kelasId = $request->input('kelas_id');
        $kelasScope = $this->kelasScope();
        $selectedKelas = $kelasScope->firstWhere('id', $kelasId);

        if (!$selectedKelas) {
            abort(403);
        }

        $tipe = $request->input('tipe');
        $tanggal = $request->input('tanggal');
        $inputTransaksi = $request->input('transaksi');
        $petugasId = Auth::id();

        $successCount = 0;
        $totalDana = 0;

        try {
            DB::transaction(function () use ($inputTransaksi, $tipe, $tanggal, $petugasId, $selectedKelas, $request, &$successCount, &$totalDana) {
                foreach ($inputTransaksi as $item) {
                    $jumlah = (float)($item['jumlah'] ?? 0);
                    if ($jumlah <= 0) {
                        continue;
                    }

                    if ($jumlah < 100) {
                        throw new \Exception("Nominal minimal transaksi adalah Rp 100.");
                    }

                    $siswa = Siswa::whereKey($item['siswa_id'])->lockForUpdate()->first();

                    if ($siswa->kelas_id != $selectedKelas->id) {
                        throw new \Exception("Siswa {$siswa->nama} tidak berada di kelas yang sesuai.");
                    }

                    if ($tipe === 'tarik' && $siswa->saldo < $jumlah) {
                        throw new \Exception("Saldo siswa '{$siswa->nama}' tidak mencukupi untuk melakukan penarikan. Saldo saat ini: Rp " . number_format($siswa->saldo, 0, ',', '.'));
                    }

                    if ($tipe === 'setor') {
                        $siswa->increment('saldo', $jumlah);
                    } else {
                        $siswa->decrement('saldo', $jumlah);
                    }

                    $keterangan = $item['keterangan'] ?? $request->input('keterangan_default') ?? ($tipe === 'setor' ? 'Setoran Harian' : 'Penarikan Harian');

                    Transaksi::create([
                        'siswa_id' => $siswa->id,
                        'user_id' => $petugasId,
                        'tipe' => $tipe,
                        'jumlah' => $jumlah,
                        'tanggal' => $tanggal,
                        'keterangan' => $keterangan,
                    ]);

                    $successCount++;
                    $totalDana += $jumlah;
                }
            });
        } catch (\Exception $e) {
            return back()->withErrors([
                'error_kolektif' => "Transaksi kolektif dibatalkan: " . $e->getMessage()
            ])->withInput();
        }

        if ($successCount === 0) {
            return redirect()->route('transaksi.kolektif.form', ['kelas_id' => $kelasId])
                ->with('info', 'Tidak ada transaksi yang disimpan (semua nominal kosong atau 0).');
        }

        $formattedTotal = number_format($totalDana, 0, ',', '.');
        return redirect()->route('transaksi.index')
            ->with('success', "Berhasil mencatat {$successCount} transaksi kolektif. Total dana: Rp {$formattedTotal}.");
    }

    /**
     * Void/correct a wrongly recorded transaction. The original record is kept
     * untouched for audit purposes; a reversing entry is created instead and
     * the student's balance is adjusted back accordingly.
     */
    public function void(Request $request, Transaksi $transaksi)
    {
        $this->authorize('void', $transaksi);

        $request->validate([
            'alasan' => 'required|string|max:255',
        ]);

        $reversalTipe = $transaksi->tipe === 'setor' ? 'tarik' : 'setor';

        try {
            $reversal = DB::transaction(function () use ($transaksi, $reversalTipe, $request) {
                $siswa = Siswa::whereKey($transaksi->siswa_id)->lockForUpdate()->first();

                if ($reversalTipe === 'tarik' && $siswa->saldo < $transaksi->jumlah) {
                    throw new InsufficientSaldoException((float) $siswa->saldo);
                }

                if ($reversalTipe === 'setor') {
                    $siswa->increment('saldo', $transaksi->jumlah);
                } else {
                    $siswa->decrement('saldo', $transaksi->jumlah);
                }

                return Transaksi::create([
                    'siswa_id' => $transaksi->siswa_id,
                    'user_id' => Auth::id(),
                    'tipe' => $reversalTipe,
                    'jumlah' => $transaksi->jumlah,
                    'tanggal' => now()->format('Y-m-d'),
                    'keterangan' => 'Koreksi/pembatalan transaksi #TX-' . str_pad($transaksi->id, 5, '0', STR_PAD_LEFT) . ': ' . $request->input('alasan'),
                    'is_reversal' => true,
                    'reversal_of_id' => $transaksi->id,
                ]);
            });
        } catch (InsufficientSaldoException $e) {
            return back()->withErrors([
                'alasan' => "Transaksi tidak dapat dibatalkan karena saldo siswa saat ini (Rp " . number_format($e->saldoTersedia, 0, ',', '.') . ") tidak mencukupi untuk menutup koreksi ini. Silakan hubungi Administrator.",
            ]);
        }

        return redirect()->route('transaksi.show', $reversal->id)
            ->with('success', 'Transaksi berhasil dibatalkan. Entri koreksi telah dicatat.');
    }

    /**
     * Classes visible to the current user: all classes for admin, only the
     * classes they are homeroom teacher of for guru.
     */
    private function kelasScope()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return Kelas::orderBy('nama_kelas')->get();
        }

        return Kelas::where('wali_kelas_id', $user->id)->orderBy('nama_kelas')->get();
    }
}
