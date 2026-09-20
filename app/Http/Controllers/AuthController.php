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
            'ruangan_id' => 'required_if:role,ruangan|nullable|integer|exists:ruangan,id',
            'password' => 'required|string',
        ], [
            'ruangan_id.required_if' => 'Silakan pilih ruangan terlebih dahulu.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $defaultPassword = (string) config('zapin.default_password', '1234');
        if ($validated['password'] !== $defaultPassword) {
            return redirect()->back()
                ->withInput($request->except('password'))
                ->with('error', 'Kata sandi salah.');
        }

        $request->session()->regenerate();

        $role = $validated['role'];

        if ($role === 'elektromedis') {
            $elektromedisRuang = Ruangan::whereRaw('LOWER(nama_ruangan) LIKE ?', ['%elektro%'])->first();
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

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
