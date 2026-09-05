@extends('layouts.app')

@section('title', 'Riwayat Pindah Ruangan Alkes')

@section('content')
<div class="space-y-6">

    @php
        $totalMutasi = $mutasiList->total();
        $totalDipindahkan = \App\Models\Alkes::whereColumn('ruangan_id', '!=', 'lokasi_ruangan_id')->count();
    @endphp

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <i class="ri-arrow-left-right-line text-indigo-600"></i>
                Riwayat & Mutasi Pindah Ruangan
            </h3>
            <p class="text-sm text-slate-700 mt-1 font-medium">Histori dan pelacakan otomatis pemindahan lokasi fisik unit alkes antar ruangan</p>
        </div>
        <a href="{{ route('mutasi.create') }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-2 shrink-0">
            <i class="ri-add-line text-lg"></i>
            <span>Pindah Ruangan Baru</span>
        </a>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
        <form method="GET" action="{{ route('mutasi.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5 uppercase">Cari Unit / SN / Pemohon</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama alkes, SN, atau pemohon..." class="w-full pl-10 pr-4 h-11 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition">
                    <i class="ri-search-line absolute left-3.5 top-3 text-slate-400 text-base"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5 uppercase">Ruangan Asal</label>
                <select name="ruangan_asal_id" class="w-full">
                    <option value="">-- Semua Ruangan Asal --</option>
                    @foreach ($ruanganList as $ruang)
                        <option value="{{ $ruang->id }}" {{ request('ruangan_asal_id') == $ruang->id ? 'selected' : '' }}>{{ $ruang->nama_ruangan }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5 uppercase">Ruangan Tujuan</label>
                <div class="flex items-center gap-2">
                    <select name="ruangan_tujuan_id" class="w-full">
                        <option value="">-- Semua Ruangan Tujuan --</option>
                        @foreach ($ruanganList as $ruang)
                            <option value="{{ $ruang->id }}" {{ request('ruangan_tujuan_id') == $ruang->id ? 'selected' : '' }}>{{ $ruang->nama_ruangan }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="h-11 px-5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition shrink-0 flex items-center justify-center">
                        <i class="ri-search-line"></i>
                    </button>
                    @if (request()->hasAny(['search', 'ruangan_asal_id', 'ruangan_tujuan_id']))
                        <a href="{{ route('mutasi.index') }}" class="h-11 w-11 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl border border-slate-300 transition flex items-center justify-center shrink-0" title="Reset">
                            <i class="ri-refresh-line text-lg"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-emerald-950 text-white border-b border-emerald-900 text-xs font-black uppercase tracking-wider">
                        <th class="py-3.5 px-4 border-r border-emerald-900">Waktu Mutasi</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Alat Kesehatan</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Perpindahan Lokasi</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Pemohon & Penanggung Jawab</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Alasan Pemindahan</th>
                        <th class="py-3.5 px-4 text-center w-32">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm font-medium text-slate-900">
                    @forelse ($mutasiList as $mutasi)
                        <tr class="hover:bg-emerald-50/40 transition odd:bg-white even:bg-slate-50/70 border-b border-slate-200">
                            <td class="py-3.5 px-4 whitespace-nowrap border-r border-slate-200">
                                <div class="font-bold text-slate-900 text-sm">{{ $mutasi->tanggal_mutasi ? $mutasi->tanggal_mutasi->format('d M Y') : '-' }}</div>
                                <div class="text-xs text-slate-500 font-mono font-bold mt-0.5">{{ $mutasi->tanggal_mutasi ? $mutasi->tanggal_mutasi->format('H:i') : '' }} WIB</div>
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200">
                                <a href="{{ route('alkes.show', $mutasi->alkes_id) }}" class="font-extrabold text-slate-900 hover:text-emerald-700 transition block text-sm">
                                    {{ $mutasi->alkes->nama_barang ?? 'Alkes' }}
                                </a>
                                <span class="text-xs text-slate-500 font-mono font-bold">SN: {{ $mutasi->alkes->nomor_seri ?? '-' }}</span>
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 text-xs font-bold border border-slate-300">
                                        {{ $mutasi->ruanganAsal->nama_ruangan ?? 'Asal' }}
                                    </span>
                                    <i class="ri-arrow-right-line text-indigo-600 font-bold text-sm"></i>
                                    <span class="px-2.5 py-1 rounded-lg bg-indigo-100 text-indigo-900 text-xs font-extrabold border border-indigo-300">
                                        {{ $mutasi->ruanganTujuan->nama_ruangan ?? 'Tujuan' }}
                                    </span>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200">
                                <div class="font-bold text-slate-900 text-sm">{{ $mutasi->pemohon }}</div>
                                <div class="text-xs text-slate-500 font-semibold mt-0.5">PJ: {{ $mutasi->penanggung_jawab }}</div>
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200 max-w-[240px]">
                                <p class="text-xs font-medium text-slate-800 leading-relaxed">{{ $mutasi->alasan_mutasi }}</p>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">
                                    <i class="ri-checkbox-circle-fill text-emerald-600"></i> {{ $mutasi->status_persetujuan ?? 'Disetujui' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-700 font-bold">
                                <i class="ri-arrow-left-right-line text-5xl block mb-2 text-slate-400"></i>
                                Belum ada riwayat pemindahan ruangan tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 bg-slate-100/70 border-t border-slate-200">
            {{ $mutasiList->links('pagination.custom') }}
        </div>
    </div>

</div>
@endsection
