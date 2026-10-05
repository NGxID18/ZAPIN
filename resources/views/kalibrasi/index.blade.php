@extends('layouts.app')

@section('title', 'Kalibrasi Alat Kesehatan')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <i class="ri-verified-badge-line text-emerald-600"></i>
                Kalibrasi & Pengujian Berkala Alkes
            </h3>
        </div>
        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <a href="{{ config('zapin.google_sheet_url') }}" target="_blank" rel="noopener noreferrer" class="px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-300 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2" title="Buka Portal Google Sheets Live Data">
                <i class="ri-file-excel-2-fill text-emerald-600 text-base"></i>
                <span>Buka Google Sheets</span>
            </a>

            @if (session('user_role') === 'elektromedis')
                <span class="px-3.5 py-2.5 bg-amber-400/20 text-amber-800 border border-amber-300 rounded-xl text-xs font-bold flex items-center gap-1.5 shrink-0">
                    <i class="ri-shield-check-line text-amber-600 text-sm"></i>
                    Otoritas Elektromedis
                </span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 border-l-4 border-l-teal-600 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-2xl font-bold shrink-0">
                <i class="ri-stethoscope-line"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-teal-800 uppercase tracking-wider">Total Alkes</p>
                <h3 class="text-2xl font-black text-slate-900 mt-0.5">{{ number_format($totalAlkes) }} <span class="text-xs font-semibold text-slate-600">Unit</span></h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 border-l-4 border-l-emerald-600 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl font-bold shrink-0">
                <i class="ri-checkbox-circle-line"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Terkalibrasi (Valid)</p>
                <h3 class="text-2xl font-black text-emerald-700 mt-0.5">{{ number_format($totalTerkalibrasi) }} <span class="text-xs font-semibold text-slate-600">Unit</span></h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 border-l-4 border-l-rose-600 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl font-bold shrink-0">
                <i class="ri-alarm-warning-line"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-rose-800 uppercase tracking-wider">Expired / Kalibrasi</p>
                <h3 class="text-2xl font-black text-rose-700 mt-0.5">{{ number_format($totalExpired) }} <span class="text-xs font-semibold text-slate-600">Unit</span></h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 border-l-4 border-l-amber-500 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl font-bold shrink-0">
                <i class="ri-time-line"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-amber-800 uppercase tracking-wider">Belum Dikalibrasi</p>
                <h3 class="text-2xl font-black text-amber-700 mt-0.5">{{ number_format($totalBelum) }} <span class="text-xs font-semibold text-slate-600">Unit</span></h3>
            </div>
        </div>
    </div>

    @if (($totalH7 ?? 0) > 0 || ($totalH30 ?? 0) > 0)
        <div class="p-4 rounded-2xl bg-gradient-to-r from-amber-50 to-rose-50 border border-amber-300 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-xl shrink-0 animate-pulse">
                    <i class="ri-alarm-warning-line"></i>
                </div>
                <div>
                    <h4 class="font-extrabold text-slate-900 text-sm flex items-center gap-2 flex-wrap">
                        Early Warning System (EWS) Kalibrasi Aktif
                        @if (($totalH7 ?? 0) > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white animate-bounce">{{ $totalH7 }} Unit Kritis (H-7)</span>
                        @endif
                        @if (($totalH30 ?? 0) > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white">{{ $totalH30 }} Unit Peringatan (H-30)</span>
                        @endif
                    </h4>
                    <p class="text-xs text-slate-600 mt-0.5 font-medium">
                        Terdapat alkes yang mendekati tenggat masa uji kalibrasi. Notifikasi dua tahap (H-30 dan H-7) otomatis diterbitkan untuk elektromedis.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if (($totalH7 ?? 0) > 0)
                    <a href="{{ route('kalibrasi.index', ['status_kalibrasi' => 'H-7']) }}" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                        Lihat H-7 ({{ $totalH7 }})
                    </a>
                @endif
                @if (($totalH30 ?? 0) > 0)
                    <a href="{{ route('kalibrasi.index', ['status_kalibrasi' => 'H-30']) }}" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition shadow-xs">
                        Lihat H-30 ({{ $totalH30 }})
                    </a>
                @endif
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-sm">
        <form method="GET" action="{{ route('kalibrasi.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
            <div class="lg:col-span-5">
                <label class="block text-xs font-bold text-slate-800 mb-1.5 uppercase">Cari Alat / Serial Number</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama alkes, merk, atau nomor seri..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition">
                    <i class="ri-search-line absolute left-3.5 top-3 text-slate-400"></i>
                </div>
            </div>

            <div class="lg:col-span-3">
                <label class="block text-xs font-bold text-slate-800 mb-1.5 uppercase">Ruangan Pemilik</label>
                <select name="ruangan_id" class="w-full">
                    <option value="">-- Semua Ruangan --</option>
                    @foreach ($ruanganList as $r)
                        <option value="{{ $r->id }}" {{ request('ruangan_id') == $r->id ? 'selected' : '' }}>{{ $r->nama_ruangan }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-3">
                <label class="block text-xs font-bold text-slate-800 mb-1.5 uppercase">Status Kalibrasi</label>
                <select name="status_kalibrasi" class="w-full">
                    <option value="">-- Semua Status --</option>
                    <option value="TERKALIBRASI" {{ request('status_kalibrasi') == 'TERKALIBRASI' ? 'selected' : '' }}>Terkalibrasi (Aktif)</option>
                    <option value="H-7" {{ request('status_kalibrasi') == 'H-7' ? 'selected' : '' }}>EWS Kritis: H-7 (&le; 7 Hari)</option>
                    <option value="H-30" {{ request('status_kalibrasi') == 'H-30' ? 'selected' : '' }}>EWS Peringatan: H-30 (8 - 30 Hari)</option>
                    <option value="EXPIRED" {{ request('status_kalibrasi') == 'EXPIRED' ? 'selected' : '' }}>Kadaluarsa / Expired</option>
                    <option value="BELUM" {{ request('status_kalibrasi') == 'BELUM' ? 'selected' : '' }}>Belum Pernah Dikalibrasi</option>
                </select>
            </div>

            <div class="lg:col-span-1">
                <button type="submit" class="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold text-sm transition shadow-xs flex items-center justify-center">
                    <i class="ri-filter-3-line text-lg"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm overflow-hidden min-w-0">
        <div class="overflow-x-auto w-full scrollbar-thin">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-emerald-950 text-white text-xs font-black uppercase tracking-wider border-b border-emerald-900">
                        <th class="py-3.5 px-4 text-center w-12 border-r border-emerald-900">No</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Nama Alkes</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Merk / Tipe / SN</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Ruangan</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Kondisi</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Kalibrasi Terakhir</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Jadwal Ulang</th>
                        <th class="py-3.5 px-4 text-center border-r border-emerald-900">Sertifikat / Dokumen</th>
                        <th class="py-3.5 px-4 text-center w-36">Aksi & Catatan</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 font-medium text-slate-900 text-sm">
                    @forelse ($alkesList as $index => $item)
                        @php
                            $today = \Carbon\Carbon::today();
                            $tglTerakhir = $item->tanggal_kalibrasi_terakhir;
                            $tglBerikutnya = $item->tanggal_kalibrasi_berikutnya;
                            $isExpired = $tglBerikutnya && $tglBerikutnya->isBefore($today);
                            $pdfHistory = is_array($item->sertifikat_kalibrasi_history) ? $item->sertifikat_kalibrasi_history : [];
                        @endphp

                        <tr class="hover:bg-emerald-50/40 transition odd:bg-white even:bg-slate-50/70 border-b border-slate-200">
                            <td class="py-3.5 px-4 text-center text-slate-700 font-bold border-r border-slate-200">{{ $alkesList->firstItem() + $index }}</td>

                            <td class="py-3.5 px-4 border-r border-slate-200">
                                <span class="font-extrabold text-slate-900 text-sm block">{{ $item->nama_barang }}</span>
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200 text-slate-800">
                                <span class="font-bold block text-xs">{{ $item->merk ?: '-' }} {{ $item->tipe ? '('.$item->tipe.')' : '' }}</span>
                                <span class="text-xs text-slate-500 font-mono font-bold">SN: {{ $item->nomor_seri ?: '-' }}</span>
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-800 rounded-lg text-xs font-bold inline-block border border-slate-300">
                                    {{ $item->ruangan->nama_ruangan ?? 'RS' }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200">
                                <span class="px-2.5 py-0.5 rounded text-xs font-black border {{ $item->kondisi_enum->warnaBadge() }}">{{ $item->kondisi_enum->label() }}</span>
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200">
                                @if ($tglTerakhir)
                                    <span class="text-xs text-slate-900 font-bold flex items-center gap-1.5">
                                        <i class="ri-calendar-check-line text-emerald-600"></i>
                                        {{ $tglTerakhir->format('d/m/Y') }}
                                    </span>
                                @elseif ($item->status_kalibrasi === 'SUDAH DIKALIBRASI')
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">SUDAH DIKALIBRASI</span>
                                @else
                                    <span class="text-xs text-slate-400 italic">Belum Ada</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200">
                                @if ($tglBerikutnya)
                                    @php
                                        $daysLeft = (int) $today->diffInDays($tglBerikutnya, false);
                                    @endphp
                                    <div class="space-y-1">
                                        <span class="text-xs font-bold flex items-center gap-1.5 {{ $daysLeft < 0 ? 'text-rose-700' : ($daysLeft <= 7 ? 'text-rose-700 font-extrabold' : ($daysLeft <= 30 ? 'text-amber-800' : 'text-slate-900')) }}">
                                            <i class="ri-calendar-event-line {{ $daysLeft < 0 ? 'text-rose-600' : ($daysLeft <= 7 ? 'text-rose-600' : ($daysLeft <= 30 ? 'text-amber-600' : 'text-emerald-600')) }}"></i>
                                            {{ $tglBerikutnya->format('d/m/Y') }}
                                        </span>
                                        @if ($daysLeft < 0)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 inline-block">
                                                Kadaluarsa ({{ abs($daysLeft) }} hr lalu)
                                            </span>
                                        @elseif ($daysLeft <= 7)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-600 text-white border border-rose-700 inline-block animate-pulse">
                                                EWS H-7 ({{ $daysLeft }} hr lagi)
                                            </span>
                                        @elseif ($daysLeft <= 30)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 inline-block">
                                                EWS H-30 ({{ $daysLeft }} hr lagi)
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 inline-block">
                                                Valid ({{ $daysLeft }} hr)
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Belum Dijadwalkan</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center border-r border-slate-200">
                                @if (!empty($pdfHistory))
                                    @php
                                        $lastFile = end($pdfHistory)['file_path'] ?? '';
                                        $ext = strtolower(pathinfo($lastFile, PATHINFO_EXTENSION));
                                        $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                                    @endphp
                                    <div class="flex flex-col items-center gap-1">
                                        <a href="{{ asset($lastFile) }}" target="_blank" class="px-3 py-1 bg-emerald-100 hover:bg-emerald-200 text-emerald-900 border border-emerald-300 rounded-xl text-xs font-black inline-flex items-center gap-1.5 shadow-xs transition">
                                            <i class="{{ $isImg ? 'ri-image-fill text-blue-600' : 'ri-file-pdf-fill text-rose-600' }} text-sm"></i> Dokumen Terbaru
                                        </a>
                                        @if (count($pdfHistory) > 1)
                                            <button type="button" 
                                                data-nama="{{ $item->nama_barang }}" 
                                                data-history='@json($pdfHistory)' 
                                                onclick="openPdfHistoryModal(this.dataset.nama, JSON.parse(this.dataset.history || '[]'))" 
                                                class="px-2.5 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 rounded-lg text-[10px] font-extrabold transition">
                                                <i class="ri-history-line"></i> Riwayat {{ count($pdfHistory) }} Tahun
                                            </button>
                                        @endif
                                    </div>
                                @elseif ($item->sertifikat_kalibrasi)
                                    @php
                                        $lastFile = $item->sertifikat_kalibrasi;
                                        $ext = strtolower(pathinfo($lastFile, PATHINFO_EXTENSION));
                                        $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                                    @endphp
                                    <a href="{{ asset($lastFile) }}" target="_blank" class="px-3 py-1 bg-emerald-100 hover:bg-emerald-200 text-emerald-900 border border-emerald-300 rounded-xl text-xs font-black inline-flex items-center gap-1.5 shadow-xs transition">
                                        <i class="{{ $isImg ? 'ri-image-fill text-blue-600' : 'ri-file-pdf-fill text-rose-600' }} text-sm"></i> Lihat Dokumen
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400 italic">Belum ada dokumen</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    @if (session('user_role') === 'elektromedis')
                                        <button type="button" 
                                            data-id="{{ $item->id }}" 
                                            data-nama="{{ $item->nama_barang }}" 
                                            data-status="{{ $item->status_kalibrasi }}"
                                            data-tgl-terakhir="{{ $tglTerakhir ? $tglTerakhir->format('Y-m-d') : '' }}" 
                                            data-tgl-berikutnya="{{ $tglBerikutnya ? $tglBerikutnya->format('Y-m-d') : '' }}" 
                                            onclick="openUpdateModal(this.dataset.id, this.dataset.nama, this.dataset.tglTerakhir, this.dataset.tglBerikutnya, this.dataset.status)" 
                                            class="px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-xl font-bold text-xs transition flex items-center justify-center gap-1 shadow-xs">
                                            <i class="ri-edit-box-line"></i> Update
                                        </button>
                                    @endif

                                    @if ($item->keterangan || !empty($pdfHistory))
                                        <button type="button" 
                                            data-nama="{{ $item->nama_barang }}" 
                                            data-keterangan="{{ $item->keterangan }}" 
                                            data-history='@json($pdfHistory)' 
                                            onclick="openViewNoteModal(this.dataset.nama, this.dataset.keterangan, JSON.parse(this.dataset.history || '[]'))" 
                                            class="px-2.5 py-1.5 bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-300 rounded-xl font-bold text-xs transition inline-flex items-center gap-1 shadow-xs" title="Lihat Detail & Riwayat Kalibrasi">
                                            <i class="ri-file-text-line text-amber-600"></i> Detail
                                        </button>
                                    @elseif (session('user_role') !== 'elektromedis')
                                        <span class="text-xs text-slate-400 font-semibold italic">Lihat Saja</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-700 font-bold">
                                <i class="ri-file-search-line text-5xl block mb-2 text-slate-400"></i>
                                Tidak ada data alat kesehatan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($alkesList->hasPages())
            <div class="p-4 bg-slate-100/70 border-t border-slate-200">
                {{ $alkesList->links('pagination.custom') }}
            </div>
        @endif
    </div>

</div>

<div id="updateKalibrasiModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-300 overflow-hidden">
        <div class="px-5 py-4 bg-emerald-950 text-white flex items-center justify-between">
            <h4 class="font-bold text-base flex items-center gap-2">
                <i class="ri-verified-badge-line text-amber-300"></i>
                Update Sertifikat & Kalibrasi Alkes
            </h4>
            <button type="button" onclick="closeUpdateModal()" class="text-slate-300 hover:text-white p-1 rounded-lg transition">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        <form id="updateKalibrasiForm" method="POST" action="" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Alat Kesehatan</label>
                <input type="text" id="modalNamaAlkes" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-sm font-bold text-slate-900" readonly>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase mb-1.5">Status Kelayakan Kalibrasi <span class="text-rose-600">*</span></label>
                <select name="status_kalibrasi" id="modalStatusKalibrasi" required class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600">
                    <option value="SUDAH DIKALIBRASI">SUDAH DIKALIBRASI (Layak & Terverifikasi)</option>
                    <option value="BELUM DIKALIBRASI">BELUM DIKALIBRASI</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-800 uppercase mb-1.5">Tanggal Kalibrasi <span class="text-rose-600">*</span></label>
                    <input type="date" name="tanggal_kalibrasi_terakhir" id="modalTglTerakhir" onchange="autoCalculateNextDate(this.value)" class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-800 uppercase mb-1.5">Jadwal Ulang <span class="text-rose-600">*</span></label>
                    <input type="date" name="tanggal_kalibrasi_berikutnya" id="modalTglBerikutnya" class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600" required>
                    <span class="text-[10px] text-slate-500 mt-1 font-semibold block">*Otomatis +1 tahun</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase mb-1.5">Unggah Sertifikat / Laporan Kalibrasi (PDF / Gambar)</label>
                <input type="file" name="sertifikat_pdf" accept=".pdf,.jpg,.jpeg,.png,.webp,image/*" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                <span class="text-[10px] text-slate-500 mt-1 font-semibold block">*Format: PDF atau Gambar (JPG, PNG, WEBP), Maks 10MB. Dokumen baru otomatis diarsipkan tanpa menimpa dokumen sebelumnya.</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase mb-1.5">Nomor Sertifikat / Catatan</label>
                <textarea name="keterangan" rows="2" placeholder="Nomor sertifikat kalibrasi atau catatan pengujian..." class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                <button type="button" onclick="closeUpdateModal()" class="px-5 py-2.5 bg-slate-100 text-slate-800 hover:bg-slate-200 rounded-xl text-xs font-bold transition">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/30 transition flex items-center gap-1.5">
                    <i class="ri-save-line"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<div id="viewNoteModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-300 overflow-hidden">
        <div class="px-5 py-4 bg-emerald-950 text-white flex items-center justify-between">
            <h4 class="font-bold text-base flex items-center gap-2">
                <i class="ri-file-text-line text-amber-300"></i>
                Detail & Riwayat Kalibrasi Multi-Tahun
            </h4>
            <button type="button" onclick="closeViewNoteModal()" class="text-slate-300 hover:text-white p-1 rounded-lg transition">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Alat Kesehatan</label>
                <p id="viewModalNamaAlkes" class="font-extrabold text-slate-900 text-base"></p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Riwayat & Catatan Elektromedis</label>
                <div id="viewModalKeterangan" class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 whitespace-pre-line leading-relaxed"></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Arsip Dokumen Sertifikat Berbagai Tahun</label>
                <div id="viewModalPdfHistory" class="space-y-2"></div>
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end">
                <button type="button" onclick="closeViewNoteModal()" class="px-5 py-2 bg-slate-100 text-slate-800 hover:bg-slate-200 rounded-xl text-xs font-bold transition">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    function openUpdateModal(id, namaAlkes, tglTerakhir, tglBerikutnya, statusKalibrasi) {
        document.getElementById('updateKalibrasiForm').action = '/kalibrasi/' + id;
        document.getElementById('modalNamaAlkes').value = namaAlkes;
        document.getElementById('modalTglTerakhir').value = tglTerakhir || '';
        document.getElementById('modalTglBerikutnya').value = tglBerikutnya || '';
        const statusSelect = document.getElementById('modalStatusKalibrasi');
        if (statusSelect) {
            statusSelect.value = (statusKalibrasi === 'SUDAH DIKALIBRASI' || !statusKalibrasi) ? 'SUDAH DIKALIBRASI' : statusKalibrasi;
        }
        document.getElementById('updateKalibrasiModal').classList.remove('hidden');
    }

    function closeUpdateModal() {
        document.getElementById('updateKalibrasiModal').classList.add('hidden');
    }

    function openViewNoteModal(namaAlkes, keterangan, pdfHistory) {
        document.getElementById('viewModalNamaAlkes').innerText = namaAlkes;
        document.getElementById('viewModalKeterangan').innerText = keterangan || 'Tidak ada catatan khusus.';

        const pdfContainer = document.getElementById('viewModalPdfHistory');
        pdfContainer.innerHTML = '';

        if (pdfHistory && Array.isArray(pdfHistory) && pdfHistory.length > 0) {
            pdfHistory.forEach((item) => {
                const ext = (item.file_path || '').split('.').pop().toLowerCase();
                const isImg = ['jpg', 'jpeg', 'png', 'webp'].includes(ext);
                const iconClass = isImg ? 'ri-image-fill text-blue-300' : 'ri-file-pdf-fill text-rose-300';
                const card = document.createElement('div');
                card.className = 'p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl flex items-center justify-between gap-2';
                card.innerHTML = `
                    <div>
                        <span class="font-black text-xs text-emerald-950 block">Tahun ${item.tahun || '-'} (Pengujian ${item.tanggal || '-'})</span>
                        <span class="text-[11px] text-slate-600 font-medium block">${item.keterangan || 'Sertifikat Kalibrasi'}</span>
                    </div>
                    <a href="${item.file_path}" target="_blank" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs shrink-0 flex items-center gap-1 shadow-xs">
                        <i class="${iconClass}"></i> Buka Dokumen
                    </a>
                `;
                pdfContainer.appendChild(card);
            });
        } else {
            pdfContainer.innerHTML = '<span class="text-xs text-slate-400 italic">Belum ada dokumen sertifikat diarsipkan.</span>';
        }

        document.getElementById('viewNoteModal').classList.remove('hidden');
    }

    function openPdfHistoryModal(namaAlkes, pdfHistory) {
        openViewNoteModal(namaAlkes, '', pdfHistory);
    }

    function closeViewNoteModal() {
        document.getElementById('viewNoteModal').classList.add('hidden');
    }

    function autoCalculateNextDate(lastDateStr) {
        if (!lastDateStr) return;
        const lastDate = new Date(lastDateStr);
        lastDate.setFullYear(lastDate.getFullYear() + 1);
        const yyyy = lastDate.getFullYear();
        const mm = String(lastDate.getMonth() + 1).padStart(2, '0');
        const dd = String(lastDate.getDate()).padStart(2, '0');
        document.getElementById('modalTglBerikutnya').value = `${yyyy}-${mm}-${dd}`;
    }
</script>
@endsection
