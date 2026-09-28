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
            @if ($alkes->canBeOperatedByCurrentRole())
                <a href="{{ route('mutasi.create', ['alkes_id' => $alkes->id]) }}" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-lg transition flex items-center gap-1.5">
                    <i class="ri-arrow-left-right-line text-sm"></i>
                    Pindah Ruangan
                </a>
                <a href="{{ route('pemeliharaan.create', ['alkes_id' => $alkes->id]) }}" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-medium text-xs rounded-lg transition flex items-center gap-1.5">
                    <i class="ri-tools-line text-sm"></i>
                    Lapor Perbaikan
                </a>
            @endif

            @if ($alkes->canBeManagedByCurrentRole())
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5 shadow-sm">
                <h4 class="font-black text-sm text-slate-900 pb-3 border-b border-slate-100 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <i class="ri-stethoscope-line text-emerald-600 text-lg"></i>
                        Identitas & Spesifikasi Alat
                    </span>
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-900 font-extrabold text-xs rounded-lg border border-emerald-300">
                        No. Urut #{{ $alkes->no_urut }}
                    </span>
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Nama Barang</span>
                        <span class="font-extrabold text-slate-900 text-base mt-0.5 block">{{ $alkes->nama_barang }}</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Merk / Produsen</span>
                        <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $alkes->merk ?: '-' }}</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Model / Tipe</span>
                        <span class="font-semibold text-slate-800 text-sm mt-0.5 block">{{ $alkes->tipe ?: '-' }}</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Nomor Seri (Serial Number)</span>
                        <span class="font-mono font-bold text-slate-900 text-sm mt-0.5 block">{{ $alkes->nomor_seri ?: '-' }}</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Tahun</span>
                        <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $alkes->tahun ?: '-' }}</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Kode Inventaris Sistem</span>
                        <span class="font-mono font-bold text-slate-700 text-xs mt-0.5 block">{{ $alkes->kode_inventaris }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5 shadow-sm">
                <h4 class="font-black text-sm text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <i class="ri-file-shield-2-line text-teal-600 text-lg"></i>
                    Pengadaan, Distributor & Legalitas
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Cara Perolehan</span>
                        <span class="font-bold text-slate-900 text-sm mt-0.5 block">{{ $alkes->cara_perolehan ?: '-' }}</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Nilai Perolehan</span>
                        <span class="font-mono font-black text-emerald-950 text-sm mt-0.5 block">
                            {{ $alkes->nilai_perolehan ? (str_starts_with($alkes->nilai_perolehan, 'Rp') ? $alkes->nilai_perolehan : 'Rp ' . $alkes->nilai_perolehan) : '-' }}
                        </span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 sm:col-span-2">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Distributor</span>
                        <span class="font-bold text-slate-900 text-sm mt-0.5 block">{{ $alkes->distributor ?: '-' }}</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Status ASPAK</span>
                        <div class="mt-1">
                            @if (strtoupper(trim($alkes->aspak ?? '')) === 'TERDATA')
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-900 font-bold text-xs rounded-lg border border-emerald-300 inline-block">TERDATA</span>
                            @elseif (strtoupper(trim($alkes->aspak ?? '')) === 'TIDAK TERDATA')
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 font-semibold text-xs rounded-lg border border-slate-300 inline-block">TIDAK TERDATA</span>
                            @elseif ($alkes->aspak)
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg border border-slate-300 inline-block">{{ $alkes->aspak }}</span>
                            @else
                                <span class="text-slate-400 font-medium text-sm">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Status KIB</span>
                        <div class="mt-1">
                            @if (strtoupper(trim($alkes->kib ?? '')) === 'TERDATA')
                                <span class="px-2.5 py-1 bg-teal-100 text-teal-900 font-bold text-xs rounded-lg border border-teal-300 inline-block">TERDATA</span>
                            @elseif (strtoupper(trim($alkes->kib ?? '')) === 'TIDAK TERDATA')
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 font-semibold text-xs rounded-lg border border-slate-300 inline-block">TIDAK TERDATA</span>
                            @elseif ($alkes->kib)
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg border border-slate-300 inline-block">{{ $alkes->kib }}</span>
                            @else
                                <span class="text-slate-400 font-medium text-sm">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">NON KIB dan ASPAK</span>
                        <div class="mt-1">
                            @if (strtoupper(trim($alkes->non_kib_dan_aspak ?? '')) === 'FALSE')
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-600 font-mono font-bold text-xs rounded-lg border border-slate-300 inline-block">FALSE</span>
                            @elseif (strtoupper(trim($alkes->non_kib_dan_aspak ?? '')) === 'TRUE')
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-900 font-mono font-bold text-xs rounded-lg border border-amber-300 inline-block">TRUE</span>
                            @elseif ($alkes->non_kib_dan_aspak)
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-mono font-semibold text-xs rounded-lg border border-slate-300 inline-block">{{ $alkes->non_kib_dan_aspak }}</span>
                            @else
                                <span class="text-slate-400 font-medium text-sm">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Izin Edar (AKL / AKD)</span>
                        <div class="mt-1">
                            @if ($alkes->akl_akd)
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-900 font-mono font-bold text-xs rounded-lg border border-blue-200 inline-block">{{ $alkes->akl_akd }}</span>
                            @else
                                <span class="text-slate-400 font-medium text-sm">-</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-3 shadow-sm">
                <h4 class="font-black text-sm text-slate-900 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="ri-chat-1-line text-indigo-600 text-lg"></i>
                    Keterangan / Catatan Inventaris
                </h4>
                <p class="text-sm font-medium text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-100 leading-relaxed">
                    {{ $alkes->keterangan ?: 'Tidak ada catatan tambahan.' }}
                </p>
            </div>

            @if ($alkes->sertifikat_kalibrasi)
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between shadow-xs">
                    <div>
                        <span class="text-xs font-black text-emerald-950 flex items-center gap-2">
                            <i class="ri-file-text-line text-emerald-600 text-base"></i> Sertifikat Kalibrasi Resmi
                        </span>
                        <span class="text-xs text-emerald-800">Dokumen kalibrasi aktif tersedia</span>
                    </div>
                    <a href="{{ route('sertifikat.show', basename($alkes->sertifikat_kalibrasi)) }}" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                        <i class="ri-external-link-line"></i> Buka Dokumen
                    </a>
                </div>
            @endif
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
