@extends('layouts.app')

@section('title', 'Peminjaman Alat Kesehatan')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <i class="ri-exchange-line text-sky-500"></i>
                Peminjaman Alat (Sementara)
            </h3>
            <p class="text-sm text-slate-700 mt-1 font-medium">Pelacakan peminjaman alkes antar ruangan secara real-time</p>
        </div>
        <button type="button" onclick="document.getElementById('peminjamanModal').classList.remove('hidden')" class="px-5 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-2 shrink-0">
            <i class="ri-add-line text-lg"></i>
            <span>Pinjam Alat Baru</span>
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-900 text-white border-b border-slate-800 text-xs font-black uppercase tracking-wider">
                        <th class="py-3.5 px-4">Nama Alat & SN</th>
                        <th class="py-3.5 px-4">Peminjam & Ruangan</th>
                        <th class="py-3.5 px-4">Waktu Pinjam & Estimasi</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm font-medium text-slate-900">
                    @forelse ($peminjamanList as $pinjam)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4">
                                <a href="{{ route('alkes.show', $pinjam->alkes_id) }}" class="font-extrabold text-slate-900 hover:text-sky-700 transition">
                                    {{ $pinjam->alkes->nama_barang ?? 'Alkes' }}
                                </a>
                                <div class="text-xs text-slate-500 font-mono mt-0.5">SN: {{ $pinjam->alkes->nomor_seri ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold">{{ $pinjam->peminjam_nama }}</div>
                                <div class="text-xs text-slate-600">{{ $pinjam->ruanganPeminjam->nama_ruangan ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs whitespace-nowrap">
                                <div><span class="text-slate-500">Pinjam:</span> {{ $pinjam->tanggal_pinjam->format('d M Y, H:i') }}</div>
                                <div><span class="text-slate-500">Estimasi Kembali:</span> <span class="font-bold text-sky-700">{{ $pinjam->estimasi_kembali->format('d M Y, H:i') }}</span></div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if ($pinjam->status === 'Dipinjam')
                                    <span class="px-2.5 py-1 bg-sky-100 text-sky-800 border border-sky-300 rounded-lg text-xs font-bold inline-block mb-2">Dipinjam</span>
                                    <form method="POST" action="{{ route('peminjaman.kembalikan', $pinjam->id) }}" onsubmit="return confirm('Kembalikan alat ini ke ruangan asal?')">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white text-[11px] font-bold rounded-lg transition w-full">Kembalikan</button>
                                    </form>
                                @else
                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-xs font-bold inline-block">Dikembalikan</span>
                                    <div class="text-[10px] text-slate-500 mt-1">{{ $pinjam->tanggal_dikembalikan ? $pinjam->tanggal_dikembalikan->format('d M Y, H:i') : '' }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-slate-500 font-medium">Belum ada riwayat peminjaman alat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
            {{ $peminjamanList->links('pagination.custom') }}
        </div>
    </div>

</div>

<!-- Modal Form Peminjaman -->
<div id="peminjamanModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl overflow-hidden">
        <div class="px-5 py-4 bg-slate-900 text-white flex justify-between items-center">
            <h3 class="font-bold text-sm">Form Pengajuan Peminjaman</h3>
            <button type="button" onclick="document.getElementById('peminjamanModal').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="ri-close-line text-lg"></i></button>
        </div>
        <form method="POST" action="{{ route('peminjaman.store') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ID Alat Kesehatan *</label>
                <input type="text" name="alkes_id" placeholder="Masukkan ID Alat (bisa dilihat di URL detail alkes)" required class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Ruangan Peminjam *</label>
                <select name="ruangan_peminjam_id" required class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm">
                    @foreach($ruanganList as $ruangan)
                        <option value="{{ $ruangan->id }}">{{ $ruangan->nama_ruangan }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Peminjam *</label>
                <input type="text" name="peminjam_nama" required class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tgl Pinjam *</label>
                    <input type="datetime-local" name="tanggal_pinjam" value="{{ date('Y-m-d\TH:i') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tgl Kembali *</label>
                    <input type="datetime-local" name="estimasi_kembali" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan / Tujuan</label>
                <textarea name="keterangan" rows="2" class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm"></textarea>
            </div>
            <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('peminjamanModal').classList.add('hidden')" class="px-4 py-2 bg-slate-100 rounded-xl text-sm font-bold">Batal</button>
                <button type="submit" class="px-5 py-2 bg-sky-500 text-white rounded-xl text-sm font-bold shadow-md">Simpan Peminjaman</button>
            </div>
        </form>
    </div>
</div>
@endsection
