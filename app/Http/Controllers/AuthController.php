<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (session()->has('user_role')) {
            return redirect()->route('dashboard');
        }

        $ruanganList = Ruangan::orderBy('nama_ruangan', 'asc')->get();
        return view('auth.login', compact('ruanganList'));
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|string|in:elektromedis,ruangan,tata_usaha',
            'ruangan_id' => 'nullable|integer|exists:ruangan,id',
        ]);

        $role = $validated['role'];

        if ($role === 'elektromedis') {
            $elektromedisRuang = Ruangan::where('nama_ruangan', 'Elektromedis')->first();
            session([
                'user_role' => 'elektromedis',
                'user_role_label' => 'Instalasi Elektromedis',
                'user_ruangan_id' => $elektromedisRuang ? $elektromedisRuang->id : 1,
                'user_ruangan_name' => 'Elektromedis',
            ]);
            $msg = 'Berhasil masuk sebagai Instalasi Elektromedis.';
        } elseif ($role === 'tata_usaha') {
            session([
                'user_role' => 'tata_usaha',
                'user_role_label' => 'Manajemen / Penunjang (Read-Only)',
                'user_ruangan_id' => 0,
                'user_ruangan_name' => 'Manajemen & Penunjang',
            ]);
            $msg = 'Berhasil masuk sebagai Manajemen / Penunjang (Pengawasan Read-Only).';
        } else {
            $ruanganId = (int) ($validated['ruangan_id'] ?? 1);
            $ruangan = Ruangan::find($ruanganId);
            $namaRuangan = $ruangan ? $ruangan->nama_ruangan : 'Instalasi / Ruangan';
            session([
                'user_role' => 'ruangan',
                'user_role_label' => "Instalasi / Ruangan {$namaRuangan}",
                'user_ruangan_id' => $ruanganId,
                'user_ruangan_name' => $namaRuangan,
            ]);
            $msg = "Berhasil masuk sebagai Instalasi / Ruangan {$namaRuangan}.";
        }

        return redirect()->route('dashboard')->with('success', $msg);
    }

    public function logout()
    {
        session()->forget(['user_role', 'user_role_label', 'user_ruangan_id', 'user_ruangan_name']);
        return redirect()->route('login')->with('success', 'Anda telah keluar dari sistem ZAPIN.');
    }
}
