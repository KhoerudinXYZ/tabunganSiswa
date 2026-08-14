<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GuruController extends Controller
{
    /**
     * Display a listing of the teachers.
     */
    public function index()
    {
        $guruList = User::where('role', 'guru')
            ->with('kelasDiampu')
            ->orderBy('name')
            ->get();

        return view('guru.index', compact('guruList'));
    }

    /**
     * Store a newly created teacher in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'role' => 'guru',
        ]);

        return redirect()->route('guru.index')->with('success', 'Akun Guru baru berhasil ditambahkan.');
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(Request $request, User $guru)
    {
        if ($guru->role !== 'guru') {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $guru->id,
            'password' => 'nullable|string|min:8',
        ]);

        $guru->name = $request->input('name');
        $guru->email = $request->input('email');

        if ($request->filled('password')) {
            $guru->password = Hash::make($request->input('password'));
        }

        $guru->save();

        return redirect()->route('guru.index')->with('success', 'Data Guru berhasil diperbarui.');
    }

    /**
     * Remove the specified teacher from storage.
     */
    public function destroy(User $guru)
    {
        if ($guru->role !== 'guru') {
            abort(403);
        }

        if ($guru->kelasDiampu()->count() > 0) {
            $classes = $guru->kelasDiampu->pluck('nama_kelas')->implode(', ');
            return redirect()->route('guru.index')->withErrors([
                'delete_error' => "Guru '{$guru->name}' tidak dapat dihapus karena saat ini menjabat sebagai wali kelas untuk: {$classes}. Harap alihkan wali kelas terlebih dahulu."
            ]);
        }

        $guru->delete();

        return redirect()->route('guru.index')->with('success', 'Akun Guru berhasil dihapus.');
    }
}
