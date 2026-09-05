@extends('layouts.app')

@section('title', 'Edit Inventaris Alkes - ' . $alkes->nama_barang)

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('alkes.show', $alkes->id) }}" class="p-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition text-slate-500 hover:text-slate-700">
            <i class="ri-arrow-left-line text-base"></i>
        </a>
        <div>
            <h3 class="text-lg font-bold text-slate-900 tracking-tight">Edit Data Inventaris Alkes</h3>
            <p class="text-xs text-slate-500">Memperbarui informasi: <span class="font-semibold">{{ $alkes->nama_barang }}</span></p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6">

        <form method="POST" action="{{ route('alkes.update', $alkes->id) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <input type="hidden" name="kode_inventaris" value="{{ $alkes->kode_inventaris }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Nama Barang / Alat Kesehatan <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_barang" value="{{ old('nama_barang', $alkes->nama_barang) }}" required class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition">
                    @error('nama_barang') <p class="text-xs text-rose-500 mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Nomor Seri (Serial Number)</label>
                    <input type="text" name="nomor_seri" value="{{ old('nomor_seri', $alkes->nomor_seri) }}" placeholder="Contoh: SN-9812-77X" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Merk / Produsen</label>
                    <input type="text" name="merk" value="{{ old('merk', $alkes->merk) }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Model / Tipe</label>
                    <input type="text" name="tipe" value="{{ old('tipe', $alkes->tipe) }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Tahun Pengadaan</label>
                    <input type="text" name="tahun_pengadaan" value="{{ old('tahun_pengadaan', $alkes->tahun_pengadaan) }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Jumlah Unit</label>
                    <input type="number" name="jumlah" value="{{ old('jumlah', $alkes->jumlah) }}" min="1" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Penempatan Ruangan <span class="text-rose-500">*</span></label>
                    <select name="ruangan_id" required class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400">
                        @foreach ($ruanganList as $ruang)
                            <option value="{{ $ruang->id }}" {{ old('ruangan_id', $alkes->ruangan_id) == $ruang->id ? 'selected' : '' }}>
                                {{ $ruang->nama_ruangan }} ({{ $ruang->lokasi_lantai ?? 'RS' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Status Alat <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400">
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" {{ old('status', $alkes->status->value ?? $alkes->status) == $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Kondisi Fisik <span class="text-rose-500">*</span></label>
                    <select name="kondisi" required class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400">
                        @foreach ($kondisis as $kd)
                            <option value="{{ $kd->value }}" {{ old('kondisi', $alkes->kondisi->value ?? $alkes->kondisi) == $kd->value ? 'selected' : '' }}>{{ $kd->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Status ASPAK</label>
                    <select name="aspak_status" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400">
                        <option value="TERDATA" {{ old('aspak_status', $alkes->aspak_status) == 'TERDATA' ? 'selected' : '' }}>TERDATA</option>
                        <option value="TIDAK TERDATA" {{ old('aspak_status', $alkes->aspak_status) == 'TIDAK TERDATA' ? 'selected' : '' }}>TIDAK TERDATA</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Status KIB</label>
                    <select name="kib_status" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400">
                        <option value="0" {{ !old('kib_status', $alkes->kib_status) ? 'selected' : '' }}>NON-KIB</option>
                        <option value="1" {{ old('kib_status', $alkes->kib_status) ? 'selected' : '' }}>TERDAFTAR KIB</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Keterangan / Catatan</label>
                    <textarea name="keterangan" rows="3" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition">{{ old('keterangan', $alkes->keterangan) }}</textarea>
                </div>

            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                <a href="{{ route('alkes.show', $alkes->id) }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium text-sm rounded-lg transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg transition flex items-center gap-1.5">
                    <i class="ri-save-line text-base"></i>
                    Simpan Perubahan
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
