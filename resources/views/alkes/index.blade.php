@extends('layouts.app')

@php
    $currentRuanganId = request('ruangan_id', 0);
    $selectedRuanganObj = $ruanganList->firstWhere('id', $currentRuanganId);
    $pageTitle = $selectedRuanganObj ? 'Inventaris Alkes Ruang ' . $selectedRuanganObj->nama_ruangan : 'Daftar Seluruh Inventaris Alkes';

    $sortBy = request('sort_by', 'nama_barang');
    $sortDir = strtolower(request('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
    $currentRole = session('user_role', 'elektromedis');
@endphp

@section('title', $pageTitle)

@section('content')

<div class="space-y-6">

    @if (session('error'))
        <div id="flashErrMsg" class="p-4 bg-rose-50 border border-rose-300 rounded-xl text-rose-900 font-bold text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <i class="ri-error-warning-fill text-rose-600 text-xl"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('flashErrMsg').remove()" class="text-rose-600 hover:text-rose-900 text-xl">
                <i class="ri-close-line"></i>
            </button>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <i class="ri-stethoscope-line text-emerald-600"></i>
                {{ $pageTitle }}
            </h3>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <form method="POST" action="{{ route('alkes.sync-sheets') }}" class="inline" onsubmit="return confirm('Mulai sinkronisasi data dari Google Spreadsheet sekarang?')">
                @csrf
                <button type="submit" class="px-4 py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2" title="Tarik data terbaru dari Google Spreadsheet ke PostgreSQL ZAPIN">
                    <i class="ri-refresh-line text-lg"></i>
                    <span>Sinkronkan Spreadsheet</span>
                </button>
            </form>

            <a href="{{ config('zapin.google_sheet_url') }}" target="_blank" rel="noopener noreferrer" class="px-4 py-3 bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-300 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2" title="Buka Portal Google Sheets Live Data">
                <i class="ri-file-excel-2-fill text-emerald-600 text-lg"></i>
                <span>Buka Google Sheets</span>
            </a>

            @if (in_array($currentRole, ['elektromedis', 'ruangan']))
                <a href="{{ route('alkes.create') }}" class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-2">
                    <i class="ri-add-line text-lg"></i>
                    <span>Tambah Alkes Baru</span>
                </a>
            @endif
        </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm space-y-3">
        <label class="block text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
            <i class="ri-search-2-line text-emerald-600 text-base"></i>
            Pencarian Universal Data
        </label>
        <form method="GET" action="{{ route('alkes.index') }}" class="flex items-center gap-3">
            @foreach (request()->except(['search', 'page']) as $k => $v)
                @if ($v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach

            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci: nama barang, merk, tipe, serial number, atau ruangan..." class="w-full pl-11 pr-4 h-11 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 shadow-xs transition">
                <i class="ri-search-line absolute left-3.5 top-3 text-slate-400 text-lg"></i>
            </div>

            <button type="submit" class="h-11 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-xs transition flex items-center gap-2 shrink-0">
                <i class="ri-search-line text-base"></i> Cari Data
            </button>

            @if (request('search'))
                <a href="{{ route('alkes.index', request()->except('search')) }}" class="h-11 px-4 bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-800 font-bold text-sm rounded-xl border border-slate-300 transition flex items-center justify-center shrink-0" title="Bersihkan Pencarian">
                    <i class="ri-close-line text-lg"></i> Reset
                </a>
            @endif
        </form>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm space-y-4">
        <label class="block text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2 pb-3 border-b border-slate-200">
            <i class="ri-filter-3-line text-emerald-600 text-base"></i>
            Filter Utama Inventaris
        </label>

        <form method="GET" action="{{ route('alkes.index') }}" id="filterForm" class="space-y-4">
            @if (request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif
            @if (request('sort_by'))
                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
            @endif
            @if (request('sort_dir'))
                <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Ruangan Pemilik Aset</label>
                    <select id="selectRuangan" name="ruangan_id" class="w-full">
                        <option value="">-- Semua Ruangan Pemilik --</option>
                        @foreach ($ruanganList as $ruang)
                            <option value="{{ $ruang->id }}" {{ request('ruangan_id') == $ruang->id ? 'selected' : '' }}>
                                {{ $ruang->nama_ruangan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Lokasi Alkes</label>
                    <select id="selectLokasi" name="lokasi_ruangan_id" class="w-full">
                        <option value="">-- Semua Lokasi Alkes --</option>
                        @foreach ($ruanganList as $ruang)
                            <option value="{{ $ruang->id }}" {{ request('lokasi_ruangan_id') == $ruang->id ? 'selected' : '' }}>
                                {{ $ruang->nama_ruangan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">Kondisi Alat</label>
                    <select id="selectKondisi" name="kondisi" class="w-full">
                        <option value="">-- Semua Kondisi Alat --</option>
                        @foreach ($kondisis as $kd)
                            <option value="{{ $kd->value }}" {{ strtolower(request('kondisi')) == strtolower($kd->value) ? 'selected' : '' }}>{{ $kd->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3 justify-end pt-3 border-t border-slate-200">
                <a href="{{ route('alkes.index') }}" class="h-10 px-5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl border border-slate-300 transition flex items-center justify-center gap-1.5 shrink-0">
                    <i class="ri-refresh-line text-sm"></i> Reset Filter
                </a>
                <button type="submit" class="h-10 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 shrink-0">
                    <i class="ri-filter-3-line text-sm"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm overflow-hidden min-w-0">
        <div class="overflow-x-auto w-full scrollbar-thin">
            <table class="w-full text-left border-collapse text-sm min-w-[2400px]">
                <thead>
                    <tr class="bg-emerald-950 text-white border-b border-emerald-900 text-xs font-black uppercase tracking-wider select-none">
                        <th class="px-3 py-3.5 text-center border-r border-emerald-900 w-14">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'no_urut', 'sort_dir' => ($sortBy === 'no_urut' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-amber-300 transition" title="Urutkan No">
                                <span>No.</span>
                                <i class="ri-arrow-up-down-line text-xs {{ $sortBy == 'no_urut' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-4 py-3.5 border-r border-emerald-900 min-w-[200px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nama_barang', 'sort_dir' => ($sortBy === 'nama_barang' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-between hover:text-amber-300 transition" title="Urutkan Nama Barang">
                                <span>Nama Barang</span>
                                <i class="ri-arrow-up-down-line text-sm {{ $sortBy == 'nama_barang' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3.5 py-3.5 border-r border-emerald-900 min-w-[120px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'merk', 'sort_dir' => ($sortBy === 'merk' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-between hover:text-amber-300 transition" title="Urutkan Merk">
                                <span>Merk</span>
                                <i class="ri-arrow-up-down-line text-sm {{ $sortBy == 'merk' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3.5 py-3.5 border-r border-emerald-900 min-w-[120px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'tipe', 'sort_dir' => ($sortBy === 'tipe' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-between hover:text-amber-300 transition" title="Urutkan Tipe">
                                <span>Tipe</span>
                                <i class="ri-arrow-up-down-line text-sm {{ $sortBy == 'tipe' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3.5 py-3.5 border-r border-emerald-900 min-w-[140px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nomor_seri', 'sort_dir' => ($sortBy === 'nomor_seri' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-between hover:text-amber-300 transition" title="Urutkan Serial Number">
                                <span>Serial Number</span>
                                <i class="ri-arrow-up-down-line text-sm {{ $sortBy == 'nomor_seri' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3 py-3.5 text-center border-r border-emerald-900 min-w-[80px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'tahun', 'sort_dir' => ($sortBy === 'tahun' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-amber-300 transition" title="Urutkan Tahun">
                                <span>Tahun</span>
                                <i class="ri-arrow-up-down-line text-xs {{ $sortBy == 'tahun' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3.5 py-3.5 border-r border-emerald-900 min-w-[140px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'cara_perolehan', 'sort_dir' => ($sortBy === 'cara_perolehan' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-between hover:text-amber-300 transition" title="Urutkan Cara Perolehan">
                                <span>Cara Perolehan</span>
                                <i class="ri-arrow-up-down-line text-sm {{ $sortBy == 'cara_perolehan' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3.5 py-3.5 border-r border-emerald-900 min-w-[150px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nilai_perolehan', 'sort_dir' => ($sortBy === 'nilai_perolehan' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-between hover:text-amber-300 transition" title="Urutkan Nilai Perolehan">
                                <span>Nilai Perolehan</span>
                                <i class="ri-arrow-up-down-line text-sm {{ $sortBy == 'nilai_perolehan' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3.5 py-3.5 border-r border-emerald-900 min-w-[140px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'distributor', 'sort_dir' => ($sortBy === 'distributor' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-between hover:text-amber-300 transition" title="Urutkan Distributor">
                                <span>Distributor</span>
                                <i class="ri-arrow-up-down-line text-sm {{ $sortBy == 'distributor' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3.5 py-3.5 border-r border-emerald-900 min-w-[140px]">Ruangan</th>
                        <th class="px-3.5 py-3.5 border-r border-emerald-900 min-w-[140px]">Lokasi Saat Ini</th>
                        <th class="px-3.5 py-3.5 text-center border-r border-emerald-900 min-w-[130px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'kondisi', 'sort_dir' => ($sortBy === 'kondisi' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-amber-300 transition" title="Urutkan Kondisi">
                                <span>Kondisi Alat</span>
                                <i class="ri-arrow-up-down-line text-sm {{ $sortBy == 'kondisi' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3.5 py-3.5 text-center border-r border-emerald-900 min-w-[120px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'aspak', 'sort_dir' => ($sortBy === 'aspak' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-amber-300 transition" title="Urutkan ASPAK">
                                <span>ASPAK</span>
                                <i class="ri-arrow-up-down-line text-xs {{ $sortBy == 'aspak' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3.5 py-3.5 text-center border-r border-emerald-900 min-w-[120px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'kib', 'sort_dir' => ($sortBy === 'kib' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-amber-300 transition" title="Urutkan KIB">
                                <span>KIB</span>
                                <i class="ri-arrow-up-down-line text-xs {{ $sortBy == 'kib' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3.5 py-3.5 text-center border-r border-emerald-900 min-w-[140px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'non_kib_dan_aspak', 'sort_dir' => ($sortBy === 'non_kib_dan_aspak' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-amber-300 transition" title="Urutkan NON KIB dan ASPAK">
                                <span>NON KIB dan ASPAK</span>
                                <i class="ri-arrow-up-down-line text-xs {{ $sortBy == 'non_kib_dan_aspak' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-3.5 py-3.5 border-r border-emerald-900 min-w-[130px]">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'akl_akd', 'sort_dir' => ($sortBy === 'akl_akd' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-between hover:text-amber-300 transition" title="Urutkan AKL/AKD">
                                <span>AKL/AKD</span>
                                <i class="ri-arrow-up-down-line text-xs {{ $sortBy == 'akl_akd' ? 'text-amber-300 opacity-100' : 'opacity-50' }}"></i>
                            </a>
                        </th>
                        <th class="px-4 py-3.5 border-r border-emerald-900 min-w-[160px]">Keterangan</th>
                        <th class="px-3.5 py-3.5 text-center border-r border-emerald-900 min-w-[130px]">Status Kalibrasi</th>
                        <th class="px-4 py-3.5 text-center w-36 sticky right-0 bg-emerald-950 z-20 shadow-[-4px_0_8px_rgba(0,0,0,0.25)] border-l border-emerald-900">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-medium text-slate-900 text-sm">
                    @forelse ($alkesList as $index => $alkes)
                        @php $rowNumber = $alkesList->firstItem() + $index; @endphp
                        <tr class="group hover:bg-emerald-50/60 transition odd:bg-white even:bg-slate-50/70 border-b border-slate-200">
                            <td class="px-3 py-3 text-center font-bold text-slate-700 border-r border-slate-200">
                                {{ $alkes->no_urut ?? $rowNumber }}
                            </td>
                            <td class="px-4 py-3 border-r border-slate-200">
                                <div class="font-extrabold text-slate-900" title="{{ $alkes->nama_barang }}">{{ $alkes->nama_barang }}</div>
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-200">
                                <div class="font-semibold text-slate-800" title="{{ $alkes->merk }}">{{ $alkes->merk ?: '-' }}</div>
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-200">
                                <div class="text-slate-800" title="{{ $alkes->tipe }}">{{ $alkes->tipe ?: '-' }}</div>
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-200">
                                <div class="font-mono font-bold text-slate-900" title="{{ $alkes->nomor_seri }}">{{ $alkes->nomor_seri ?: '-' }}</div>
                            </td>
                            <td class="px-3 py-3 text-center font-bold text-slate-800 border-r border-slate-200">
                                {{ $alkes->tahun ?: '-' }}
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-200">
                                <div class="font-semibold text-slate-800" title="{{ $alkes->cara_perolehan }}">{{ $alkes->cara_perolehan ?: '-' }}</div>
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-200 font-mono text-xs font-bold text-emerald-950">
                                {{ $alkes->nilai_perolehan ? (str_starts_with($alkes->nilai_perolehan, 'Rp') ? $alkes->nilai_perolehan : 'Rp ' . $alkes->nilai_perolehan) : '-' }}
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-200">
                                <div class="text-slate-800" title="{{ $alkes->distributor }}">{{ $alkes->distributor ?: '-' }}</div>
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-200">
                                <div class="font-bold text-slate-900 truncate" title="{{ $alkes->ruangan->nama_ruangan ?? '-' }}">{{ $alkes->ruangan->nama_ruangan ?? '-' }}</div>
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-200">
                                @if ($alkes->ruangan_id != $alkes->lokasi_ruangan_id)
                                    <span class="font-bold text-emerald-900 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300 inline-block truncate max-w-full" title="Dipinjam / Pindah dari Ruang Pemilik">{{ $alkes->lokasiRuangan->nama_ruangan ?? $alkes->lokasi_saat_ini_note ?? '-' }}</span>
                                @else
                                    <span class="text-slate-800 font-semibold inline-block truncate max-w-full" title="{{ $alkes->lokasiRuangan->nama_ruangan ?? $alkes->ruangan->nama_ruangan ?? '-' }}">{{ $alkes->lokasiRuangan->nama_ruangan ?? $alkes->ruangan->nama_ruangan ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-3 text-center border-r border-slate-200">
                                <span class="inline-block px-2.5 py-0.5 rounded text-xs font-black border {{ $alkes->kondisi_enum->warnaBadge() }}">{{ $alkes->kondisi_enum->label() }}</span>
                            </td>
                            <td class="px-3.5 py-3 text-center border-r border-slate-200">
                                @if (strtoupper(trim($alkes->aspak ?? '')) === 'TERDATA')
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">TERDATA</span>
                                @elseif (strtoupper(trim($alkes->aspak ?? '')) === 'TIDAK TERDATA')
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-300">TIDAK TERDATA</span>
                                @elseif ($alkes->aspak)
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-300">{{ $alkes->aspak }}</span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-3 text-center border-r border-slate-200">
                                @if (strtoupper(trim($alkes->kib ?? '')) === 'TERDATA')
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-teal-100 text-teal-900 border border-teal-300">TERDATA</span>
                                @elseif (strtoupper(trim($alkes->kib ?? '')) === 'TIDAK TERDATA')
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-300">TIDAK TERDATA</span>
                                @elseif ($alkes->kib)
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-300">{{ $alkes->kib }}</span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-3 text-center border-r border-slate-200">
                                @if (strtoupper(trim($alkes->non_kib_dan_aspak ?? '')) === 'FALSE')
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-mono font-semibold bg-slate-100 text-slate-600 border border-slate-300">FALSE</span>
                                @elseif (strtoupper(trim($alkes->non_kib_dan_aspak ?? '')) === 'TRUE')
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-mono font-bold bg-amber-100 text-amber-900 border border-amber-300">TRUE</span>
                                @elseif ($alkes->non_kib_dan_aspak)
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-300">{{ $alkes->non_kib_dan_aspak }}</span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-3 border-r border-slate-200">
                                @if ($alkes->akl_akd)
                                    <span class="inline-block font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-900 border border-blue-200">{{ $alkes->akl_akd }}</span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-800 border-r border-slate-200 font-medium">
                                <div class="truncate max-w-xs" title="{{ $alkes->keterangan }}">{{ $alkes->keterangan ?: '-' }}</div>
                            </td>
                            <td class="px-3.5 py-3 text-center border-r border-slate-200">
                                @if ($alkes->status_kalibrasi === 'SUDAH DIKALIBRASI')
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        <i class="ri-checkbox-circle-fill text-emerald-600"></i> SUDAH
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-700 border border-slate-300">
                                        <i class="ri-time-line text-slate-500"></i> BELUM
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap sticky right-0 bg-white group-hover:bg-emerald-50/80 z-10 shadow-[-4px_0_8px_rgba(0,0,0,0.06)] border-l border-slate-200">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('alkes.show', $alkes->id) }}" class="p-1.5 text-emerald-700 hover:bg-emerald-100 rounded-lg transition" title="Lihat Detail">
                                        <i class="ri-eye-line text-lg"></i>
                                    </a>
                                    @if ($alkes->canBeOperatedByCurrentRole())
                                        <a href="{{ route('mutasi.create', ['alkes_id' => $alkes->id]) }}" class="p-1.5 text-blue-700 hover:bg-blue-100 rounded-lg transition" title="Pindah Ruangan Alat">
                                            <i class="ri-arrow-left-right-line text-lg"></i>
                                        </a>
                                        <a href="{{ route('pemeliharaan.create', ['alkes_id' => $alkes->id]) }}" class="p-1.5 text-amber-700 hover:bg-amber-100 rounded-lg transition" title="Lapor Perbaikan">
                                            <i class="ri-tools-line text-lg"></i>
                                        </a>
                                    @endif
                                    @if ($alkes->canBeManagedByCurrentRole())
                                        <a href="{{ route('alkes.edit', $alkes->id) }}" class="p-1.5 text-slate-800 hover:bg-slate-200 rounded-lg transition" title="Edit Data">
                                            <i class="ri-edit-line text-lg"></i>
                                        </a>
                                        <form action="{{ route('alkes.destroy', $alkes->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus aset \'{{ $alkes->nama_barang }}\' (No: {{ $alkes->no_urut }}) dari ZAPIN dan Google Spreadsheet?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-100 rounded-lg transition" title="Hapus Aset">
                                                <i class="ri-delete-bin-line text-lg"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="19" class="px-6 py-12 text-center text-slate-700 font-bold">
                                <i class="ri-inbox-line text-5xl block mb-3 text-slate-400"></i>
                                Tidak ada data alat kesehatan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 bg-slate-100/70 border-t border-slate-200">
            {{ $alkesList->links('pagination.custom') }}
        </div>
    </div>

</div>

@endsection
