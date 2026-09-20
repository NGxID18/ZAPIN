@extends('layouts.app')

@section('title', 'Detail Alkes - ' . $alkes->nama_barang)

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('alkes.index') }}" class="p-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition text-slate-500 hover:text-slate-700">
                <i class="ri-arrow-left-line text-base"></i>
            </a>
            <div>
                <h3 class="text-lg font-bold text-slate-900 tracking-tight">{{ $alkes->nama_barang }}</h3>
                <span class="text-xs text-slate-500">{{ $alkes->merk ?? '-' }} &middot; {{ $alkes->tipe ?? '-' }}</span>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            @if (in_array(session('user_role'), ['elektromedis', 'ruangan']))
                <a href="{{ route('mutasi.create', ['alkes_id' => $alkes->id]) }}" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-lg transition flex items-center gap-1.5">
                    <i class="ri-arrow-left-right-line text-sm"></i>
                    Pindah Ruangan
                </a>
                <a href="{{ route('pemeliharaan.create', ['alkes_id' => $alkes->id]) }}" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-medium text-xs rounded-lg transition flex items-center gap-1.5">
                    <i class="ri-tools-line text-sm"></i>
                    Lapor Perbaikan
                </a>
            @endif

            @if (session('user_role') === 'elektromedis')
                <a href="{{ route('alkes.edit', $alkes->id) }}" class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition" title="Edit Data Alkes">
                    <i class="ri-edit-line text-sm"></i>
                </a>
                <form action="{{ route('alkes.destroy', $alkes->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus aset \'{{ $alkes->nama_barang }}\' (No: {{ $alkes->no_urut }}) dari ZAPIN dan Google Spreadsheet?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg transition" title="Hapus Aset">
                        <i class="ri-delete-bin-line text-sm"></i>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5 space-y-5">
            <h4 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100 flex items-center gap-2">
                <i class="ri-information-line text-indigo-500"></i>
                Informasi & Spesifikasi
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium uppercase block">Nama Barang</span>
                    <span class="font-semibold text-slate-900 text-sm mt-0.5 block">{{ $alkes->nama_barang }}</span>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium uppercase block">Merk / Produsen</span>
                    <span class="font-semibold text-slate-800 text-sm mt-0.5 block">{{ $alkes->merk ?? '-' }}</span>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium uppercase block">Model / Tipe</span>
                    <span class="font-medium text-slate-700 text-sm mt-0.5 block">{{ $alkes->tipe ?? '-' }}</span>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium uppercase block">Nomor Seri (SN)</span>
                    <span class="font-mono font-semibold text-slate-900 text-sm mt-0.5 block">{{ $alkes->nomor_seri ?? '-' }}</span>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium uppercase block">Status Kalibrasi</span>
                    <span class="font-bold text-sm mt-0.5 block {{ $alkes->status_kalibrasi === 'SUDAH DIKALIBRASI' ? 'text-emerald-700' : 'text-slate-700' }}">
                        {{ $alkes->status_kalibrasi ?: 'BELUM DIKALIBRASI' }}
                    </span>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium uppercase block">Tanggal Kalibrasi Terakhir</span>
                    <span class="font-medium text-slate-700 text-sm mt-0.5 block">
                        {{ $alkes->tanggal_kalibrasi_terakhir ? $alkes->tanggal_kalibrasi_terakhir->format('d/m/Y') : 'Belum ada data' }}
                    </span>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium uppercase block">Status ASPAK</span>
                    <span class="font-bold text-sm mt-0.5 block {{ $alkes->aspak === 'TERDATA' ? 'text-emerald-700' : 'text-slate-600' }}">
                        {{ $alkes->aspak ?: '-' }}
                    </span>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-[10px] text-slate-400 font-medium uppercase block">Status KIB</span>
                    <span class="font-bold text-sm mt-0.5 block {{ $alkes->kib === 'TERDATA' ? 'text-emerald-700' : 'text-slate-600' }}">
                        {{ $alkes->kib ?: '-' }}
                    </span>
                </div>
            </div>

            @if ($alkes->sertifikat_kalibrasi)
                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                            <i class="ri-file-text-line text-emerald-600"></i> Sertifikat Kalibrasi Resmi
                        </span>
                        <span class="text-[11px] text-emerald-700">Dokumen kalibrasi aktif tersedia</span>
                    </div>
                    <a href="{{ route('sertifikat.show', basename($alkes->sertifikat_kalibrasi)) }}" target="_blank" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1">
                        <i class="ri-external-link-line"></i> Buka Dokumen
                    </a>
                </div>
            @endif

            <div class="pt-3 border-t border-slate-100">
                <span class="text-[10px] text-slate-400 font-medium uppercase block mb-1">Keterangan</span>
                <p class="text-sm text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 leading-relaxed">
                    {{ $alkes->keterangan ?? 'Tidak ada catatan tambahan.' }}
                </p>
            </div>
        </div>

        <div class="space-y-4">

            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                <h4 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100">Status & Registrasi</h4>

                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-[10px] text-slate-400 block mb-1">Kondisi Fisik:</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded text-[11px] font-black border {{ $alkes->kondisi_enum->warnaBadge() }}">
                            {{ $alkes->kondisi_enum->label() }}
                        </span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 block mb-1">Status Penggunaan:</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $alkes->status_enum->warnaBadge() }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            {{ $alkes->status_enum->label() }}
                        </span>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-[10px] text-slate-400 block">Ruang Pemilik Aset:</span>
                        <span class="font-semibold text-slate-800 text-sm block mt-0.5"><i class="ri-building-line text-slate-400"></i> {{ $alkes->ruangan->nama_ruangan ?? 'RS' }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 block">Lokasi Alkes:</span>
                        <span class="font-bold text-emerald-700 text-sm block mt-0.5"><i class="ri-map-pin-line text-emerald-600"></i> {{ $alkes->lokasiRuangan->nama_ruangan ?? $alkes->ruangan->nama_ruangan ?? 'RS' }}</span>
                    </div>

                    @if ($alkes->lokasi_saat_ini_note)
                        <div class="p-2.5 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 font-medium flex items-center gap-1.5">
                            <i class="ri-information-line"></i> {{ $alkes->lokasi_saat_ini_note }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                <h4 class="font-semibold text-sm text-slate-800 pb-3 border-b border-slate-100">Riwayat Terkait</h4>
                <div class="space-y-2 text-xs">
                    <p class="text-slate-500"><span class="font-medium text-slate-700">Mutasi Ruangan:</span> {{ $alkes->mutasi->count() }} kali</p>
                    <p class="text-slate-500"><span class="font-medium text-slate-700">Log Pemeliharaan:</span> {{ $alkes->logPemeliharaan->count() }} catatan</p>
                    <p class="text-slate-500"><span class="font-medium text-slate-700">Peminjaman:</span> {{ $alkes->peminjaman->count() }} kali</p>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
