<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KelasController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Kelas::class);

        $classes = Kelas::withCount('siswas')->with('waliKelas')->get();
        $guruList = User::where('role', 'guru')->orderBy('name')->get();

        return view('kelas.index', compact('classes', 'guruList'));
    }

    /**
     * "Kelas Saya" — read-only overview for a guru of the class(es) they are
     * homeroom teacher of: class info plus the list of students and their balance.
     */
    public function saya()
    {
        $classes = Kelas::where('wali_kelas_id', Auth::id())
            ->withCount('siswas')
            ->withSum('siswas as total_tabungan', 'saldo')
            ->with(['siswas' => fn ($q) => $q->orderBy('nama')])
            ->orderBy('nama_kelas')
            ->get();

        return view('kelas.saya', compact('classes'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Kelas::class);

        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'tahun_ajaran' => 'required|string|max:20',
            'wali_kelas_id' => 'nullable|exists:users,id,role,guru',
        ]);

        Kelas::create($validated);

        return redirect()->route('kelas.index')->with('success', 'Kelas baru berhasil ditambahkan.');
    }

    public function update(Request $request, Kelas $kela)
    {
        $this->authorize('update', $kela);

        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'tahun_ajaran' => 'required|string|max:20',
            'wali_kelas_id' => 'nullable|exists:users,id,role,guru',
        ]);

        $kela->update($validated);

        return redirect()->route('kelas.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kela)
    {
        $this->authorize('delete', $kela);

        if ($kela->siswas()->count() > 0) {
            return redirect()->route('kelas.index')->withErrors([
                'delete_error' => 'Kelas tidak dapat dihapus karena masih memiliki siswa terdaftar.'
            ]);
        }

        $kela->delete();

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
