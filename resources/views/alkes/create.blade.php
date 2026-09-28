@extends('layouts.app')

@section('title', 'Tambah Inventaris Alkes Baru')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center gap-4">
        <a href="{{ route('alkes.index') }}" class="p-2.5 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition text-slate-700">
            <i class="ri-arrow-left-line text-lg"></i>
        </a>
        <div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight">Registrasi Inventaris Alkes Baru</h3>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 p-6 sm:p-8 shadow-sm">

        <form method="POST" action="{{ route('alkes.store') }}" class="space-y-6">
            @csrf

            <input type="hidden" name="kode_inventaris" value="INV/ALKES/{{ date('Y') }}/{{ sprintf('%04d', rand(1000, 9999)) }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="md:col-span-2">
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Nama Barang / Alat Kesehatan <span class="text-rose-600">*</span></label>
                    <input type="text" name="nama_barang" value="{{ old('nama_barang') }}" required placeholder="Contoh: Infus Pump, Bed Patient, Defibrillator" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                    @error('nama_barang') <p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Nomor Seri (Serial Number / SN)</label>
                    <input type="text" name="nomor_seri" value="{{ old('nomor_seri') }}" placeholder="Contoh: SN-9812-77X, SK 10308902" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-mono font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Merk / Produsen</label>
                    <input type="text" name="merk" value="{{ old('merk') }}" placeholder="Contoh: Paramount, Mindray, Philips" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Model / Tipe</label>
                    <input type="text" name="tipe" value="{{ old('tipe') }}" placeholder="Contoh: Series-90 Pro" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Tahun Pengadaan</label>
                    <input type="text" name="tahun_pengadaan" value="{{ old('tahun_pengadaan', date('Y')) }}" placeholder="Contoh: 2023" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                </div>

                <input type="hidden" name="jumlah" value="1">

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Cara Perolehan</label>
                    <input type="text" name="cara_perolehan" value="{{ old('cara_perolehan') }}" placeholder="Contoh: HIBAH APBN 2022, BLUD 2023, DAK 2023" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Nilai Perolehan (Rp)</label>
                    <input type="text" name="nilai_perolehan" value="{{ old('nilai_perolehan') }}" placeholder="Contoh: 1.672.500.000,00" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-mono font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Distributor</label>
                    <input type="text" name="distributor" value="{{ old('distributor') }}" placeholder="Contoh: PT. ETIQA PRIMA UTAMA" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Penempatan Ruangan RS <span class="text-rose-600">*</span></label>
                    @if (session('user_role') === 'ruangan')
                        @php
                            $userRoom = $ruanganList->firstWhere('id', session('user_ruangan_id'));
                        @endphp
                        <input type="hidden" name="ruangan_id" value="{{ session('user_ruangan_id') }}">
                        <div class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 flex items-center justify-between">
                            <span>{{ $userRoom ? $userRoom->nama_ruangan . ' (' . ($userRoom->lokasi_lantai ?? 'RS') . ')' : 'Ruangan Anda' }}</span>
                            <span class="text-xs bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-md font-semibold">Terkunci ke Ruangan Anda</span>
                        </div>
                    @else
                        <select name="ruangan_id" required class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                            <option value="">-- Pilih Ruangan --</option>
                            @foreach ($ruanganList as $ruang)
                                <option value="{{ $ruang->id }}" {{ old('ruangan_id') == $ruang->id ? 'selected' : '' }}>
                                    {{ $ruang->nama_ruangan }} ({{ $ruang->lokasi_lantai ?? 'RS' }})
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Status Awal Alat <span class="text-rose-600">*</span></label>
                    <select name="status" required class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}">{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Kondisi Fisik <span class="text-rose-600">*</span></label>
                    <select name="kondisi" required class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                        @foreach ($kondisis as $kd)
                            <option value="{{ $kd->value }}">{{ $kd->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Status ASPAK Kemenkes</label>
                    <select name="aspak_status" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                        <option value="TERDATA" {{ old('aspak_status') == 'TERDATA' ? 'selected' : '' }}>TERDATA</option>
                        <option value="TIDAK TERDATA" {{ old('aspak_status', 'TIDAK TERDATA') == 'TIDAK TERDATA' ? 'selected' : '' }}>TIDAK TERDATA</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Status KIB</label>
                    <select name="kib_status" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                        <option value="TERDATA" {{ old('kib_status') == 'TERDATA' ? 'selected' : '' }}>TERDATA</option>
                        <option value="TIDAK TERDATA" {{ old('kib_status', 'TIDAK TERDATA') == 'TIDAK TERDATA' ? 'selected' : '' }}>TIDAK TERDATA</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">NON KIB dan ASPAK</label>
                    <select name="non_kib_dan_aspak" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                        <option value="FALSE" {{ old('non_kib_dan_aspak', 'FALSE') == 'FALSE' ? 'selected' : '' }}>FALSE</option>
                        <option value="TRUE" {{ old('non_kib_dan_aspak') == 'TRUE' ? 'selected' : '' }}>TRUE</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Izin Edar (AKL / AKD)</label>
                    <input type="text" name="akl_akd" value="{{ old('akl_akd') }}" placeholder="Contoh: AKD 20501510565 / AKL 10901510712" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-mono font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Keterangan / Catatan Spesifikasi</label>
                    <textarea name="keterangan" rows="3" placeholder="Catatan tambahan spesifikasi..." class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">{{ old('keterangan') }}</textarea>
                </div>

            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
                <a href="{{ route('alkes.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-sm rounded-xl transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                    <i class="ri-save-line text-lg"></i>
                    Simpan Inventaris
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
