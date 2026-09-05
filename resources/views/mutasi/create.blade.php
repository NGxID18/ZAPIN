@extends('layouts.app')

@section('title', 'Formulir Pindah Ruangan Alkes')

@section('content')

<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('mutasi.index') }}" class="p-2.5 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition text-slate-600 shadow-xs">
            <i class="ri-arrow-left-line text-lg"></i>
        </a>
        <div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="ri-arrow-left-right-line text-indigo-600"></i>
                Formulir Pindah Ruangan Alkes
            </h3>
            <p class="text-xs text-slate-600 font-medium mt-0.5">Memindahkan lokasi keberadaan fisik unit alkes ke instalasi/ruangan lain</p>
        </div>
    </div>

    <form method="POST" action="{{ route('mutasi.store') }}" class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/90 shadow-sm space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-2">Pilih Alat Kesehatan <span class="text-rose-600">*</span></label>
            <select id="selectAlkesMutasi" name="alkes_id" required>
                <option value="">-- Ketik Nama Barang, SN, atau Ruangan --</option>
                @foreach ($alkesList as $alkes)
                    <option value="{{ $alkes->id }}" {{ $selectedAlkesId == $alkes->id ? 'selected' : '' }}>
                        {{ $alkes->nama_barang }} (SN: {{ $alkes->nomor_seri ?? '-' }}) - {{ $alkes->lokasiRuangan->nama_ruangan ?? $alkes->ruangan->nama_ruangan ?? 'Utama' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-2">Ruangan Tujuan Pemindahan <span class="text-rose-600">*</span></label>
            <select id="selectRuanganTujuan" name="ruangan_tujuan_id" required>
                <option value="">-- Pilih Ruangan Tujuan --</option>
                @foreach ($ruanganList as $ruang)
                    <option value="{{ $ruang->id }}">{{ $ruang->nama_ruangan }} ({{ $ruang->lokasi_lantai ?? 'RS' }})</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-2">Nama Pemohon / Penerima <span class="text-rose-600">*</span></label>
                <input type="text" name="pemohon" value="{{ old('pemohon') }}" required placeholder="Nama perawat / petugas penerima" class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition">
            </div>

            <div>
                <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-2">Penanggung Jawab <span class="text-rose-600">*</span></label>
                <input type="text" name="penanggung_jawab" value="{{ old('penanggung_jawab') }}" required placeholder="Kepala Ruangan / Dokter PJ" class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition">
            </div>
        </div>

        <div>
            <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-2">Alasan Pemindahan Lokasi <span class="text-rose-600">*</span></label>
            <textarea name="alasan_mutasi" rows="3" required placeholder="Jelaskan alasan kebutuhan operasional pemindahan..." class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition">{{ old('alasan_mutasi') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('mutasi.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl transition">Batal</a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-2">
                <i class="ri-check-double-line"></i>
                Proses Pindah Ruangan
            </button>
        </div>

    </form>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('selectAlkesMutasi') && !document.getElementById('selectAlkesMutasi').tomselect) {
            new TomSelect('#selectAlkesMutasi', { create: false, placeholder: 'Ketik nama barang, SN, atau lokasi...', maxOptions: 100 });
        }
        if (document.getElementById('selectRuanganTujuan') && !document.getElementById('selectRuanganTujuan').tomselect) {
            new TomSelect('#selectRuanganTujuan', { create: false, placeholder: 'Ketik nama ruangan tujuan...', maxOptions: 50 });
        }
    });
</script>
@endsection
