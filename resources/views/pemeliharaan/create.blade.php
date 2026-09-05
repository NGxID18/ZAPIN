@extends('layouts.app')

@section('title', 'Formulir Lapor Perbaikan & Kalibrasi Alkes')

@section('content')

<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('pemeliharaan.index') }}" class="p-2.5 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition text-slate-600 shadow-xs">
            <i class="ri-arrow-left-line text-lg"></i>
        </a>
        <div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="ri-tools-line text-amber-500"></i>
                Formulir Lapor Kerusakan Alkes
            </h3>
            <p class="text-xs text-slate-600 font-medium mt-0.5">Laporkan gejala kerusakan unit alkes untuk penanganan awal oleh Instalasi Elektromedis</p>
        </div>
    </div>

    <div id="infoCreatePemeliharaan" class="bg-amber-50/90 px-4 py-3.5 rounded-2xl border border-amber-300 text-amber-900 text-xs font-medium flex items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-base shrink-0">
                <i class="ri-information-fill"></i>
            </div>
            <span><span class="font-extrabold uppercase">Otomatisasi Lokasi:</span> Mengirimkan laporan ini akan otomatis memindahkan status lokasi fisik unit alkes ke Ruangan Elektromedis dan mencatatnya pada log mutasi.</span>
        </div>
        <button type="button" onclick="document.getElementById('infoCreatePemeliharaan').remove()" class="text-amber-600 hover:text-amber-900 p-1 rounded-lg transition">
            <i class="ri-close-line text-lg"></i>
        </button>
    </div>

    <form method="POST" action="{{ route('pemeliharaan.store') }}" enctype="multipart/form-data" class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/90 shadow-sm space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-2">Pilih Alat Kesehatan Yang Rusak <span class="text-rose-600">*</span></label>
            <select id="selectAlkesPemeliharaan" name="alkes_id" required>
                <option value="">-- Ketik Nama Barang, SN, atau Ruangan --</option>
                @foreach ($alkesList as $alkes)
                    <option value="{{ $alkes->id }}" {{ $selectedAlkesId == $alkes->id ? 'selected' : '' }}>
                        {{ $alkes->nama_barang }} (SN: {{ $alkes->nomor_seri ?? '-' }}) - {{ $alkes->ruangan->nama_ruangan ?? 'RS' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-2">Jenis Pengajuan <span class="text-rose-600">*</span></label>
                <select name="jenis_tindakan" required class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600">
                    <option value="Perbaikan (Korektif)">Perbaikan (Korektif)</option>
                    <option value="Kalibrasi Alat">Kalibrasi Alat</option>
                    <option value="Pemeliharaan Rutin">Pemeliharaan Rutin</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-2">Waktu Lapor / Pengajuan <span class="text-rose-600">*</span></label>
                <input type="datetime-local" name="tanggal_lapor" value="{{ date('Y-m-d\TH:i') }}" required class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600">
            </div>
        </div>

        <div>
            <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-2">Gejala & Kendala yang Diamati Ruangan <span class="text-rose-600">*</span></label>
            <textarea name="gejala_kerusakan" rows="4" required placeholder="Tuliskan kendala awal yang dirasakan (misal: layar mati total saat dinyalakan, bising, eror sensor 02, dll)..." class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600"></textarea>
            <p class="text-[11px] text-slate-500 font-semibold mt-1">*Catatan: Petugas ruangan memasukkan gejala awal. Diagnosa teknis & tindakan perbaikan akan diisi oleh Elektromedis saat selesai perbaikan.</p>
        </div>

        <div>
            <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-2">Lampirkan Foto Kondisi Alat (Opsional)</label>
            <input type="file" name="foto_kerusakan" accept="image/*" class="w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('pemeliharaan.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-2">
                <i class="ri-send-plane-fill"></i>
                Kirim Laporan Kerusakan
            </button>
        </div>

    </form>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var sel = document.getElementById('selectAlkesPemeliharaan');
        if (sel && !sel.tomselect) {
            new TomSelect('#selectAlkesPemeliharaan', {
                create: false,
                placeholder: '-- Ketik Nama Barang, SN, atau Ruangan --',
                maxOptions: 50,
            });
        }
    });
</script>
@endsection
