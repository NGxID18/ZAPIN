@extends('layouts.app')

@section('title', 'Perbaikan & Pemeliharaan Alkes')

@section('content')
<div class="space-y-6">

    @php
        $currentRole = session('user_role', 'elektromedis');
    @endphp

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <i class="ri-tools-line text-amber-500"></i>
                Perbaikan & Pemeliharaan Alkes
            </h3>
            <p class="text-sm text-slate-700 mt-1 font-medium">Pengawasan laporan kerusakan alkes, penanganan teknis elektromedis, dan riwayat perbaikan</p>
        </div>
        <a href="{{ route('pemeliharaan.create') }}" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-2 shrink-0">
            <i class="ri-add-line text-lg"></i>
            <span>Lapor Kerusakan Baru</span>
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 border-l-4 border-l-amber-500 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-amber-800 uppercase tracking-wider">Dalam Perbaikan (Proses)</p>
                <h3 class="text-3xl font-black text-amber-700 mt-1">{{ number_format($totalProses) }} <span class="text-xs font-semibold text-slate-600">Unit</span></h3>
                <p class="text-xs text-slate-600 font-semibold mt-1">Sedang ditangani Elektromedis</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl font-bold shrink-0">
                <i class="ri-time-line"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 border-l-4 border-l-emerald-600 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Perbaikan Selesai</p>
                <h3 class="text-3xl font-black text-emerald-700 mt-1">{{ number_format($totalSelesai) }} <span class="text-xs font-semibold text-slate-600">Unit</span></h3>
                <p class="text-xs text-slate-600 font-semibold mt-1">Telah dikembalikan ke ruangan</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl font-bold shrink-0">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 border-l-4 border-l-teal-600 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-teal-800 uppercase tracking-wider">Total Riwayat Penanganan</p>
                <h3 class="text-3xl font-black text-slate-900 mt-1">{{ number_format($totalLaporan) }} <span class="text-xs font-semibold text-slate-600">Log</span></h3>
                <p class="text-xs text-slate-600 font-semibold mt-1">Tercatat di sistem ZAPIN</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-2xl font-bold shrink-0">
                <i class="ri-history-line"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
        <form method="GET" action="{{ route('pemeliharaan.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5 uppercase">Cari Unit / SN / Diagnosa</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama alkes, serial number, atau diagnosa..." class="w-full pl-10 pr-4 h-11 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition">
                    <i class="ri-search-line absolute left-3.5 top-3 text-slate-400 text-base"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5 uppercase">Jenis Pengajuan</label>
                <select name="jenis_tindakan" class="w-full">
                    <option value="">-- Semua Jenis --</option>
                    <option value="Perbaikan (Korektif)" {{ request('jenis_tindakan') == 'Perbaikan (Korektif)' ? 'selected' : '' }}>Perbaikan (Korektif)</option>
                    <option value="Kalibrasi Alat" {{ request('jenis_tindakan') == 'Kalibrasi Alat' ? 'selected' : '' }}>Kalibrasi Alat</option>
                    <option value="Pemeliharaan Rutin" {{ request('jenis_tindakan') == 'Pemeliharaan Rutin' ? 'selected' : '' }}>Pemeliharaan Rutin</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5 uppercase">Status Penanganan</label>
                <div class="flex items-center gap-2">
                    <select name="status_hasil" class="w-full">
                        <option value="">-- Semua Status --</option>
                        <option value="Proses" {{ request('status_hasil') == 'Proses' ? 'selected' : '' }}>Dalam Perbaikan (Proses)</option>
                        <option value="Selesai" {{ request('status_hasil') == 'Selesai' ? 'selected' : '' }}>Selesai Diperbaiki</option>
                    </select>
                    <button type="submit" class="h-11 px-5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold shadow-xs transition shrink-0 flex items-center justify-center">
                        <i class="ri-search-line"></i>
                    </button>
                    @if (request()->hasAny(['search', 'jenis_tindakan', 'status_hasil']))
                        <a href="{{ route('pemeliharaan.index') }}" class="h-11 w-11 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl border border-slate-300 transition flex items-center justify-center shrink-0" title="Reset">
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
                        <th class="py-3.5 px-4 border-r border-emerald-900">Alkes & Ruangan</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Waktu Lapor & Selesai</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Gejala & Diagnosa Teknis</th>
                        <th class="py-3.5 px-4 border-r border-emerald-900">Tindakan Perbaikan</th>
                        <th class="py-3.5 px-4 text-center w-36">Aksi & Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm font-medium text-slate-900">
                    @forelse ($logList as $log)
                        <tr class="hover:bg-emerald-50/40 transition odd:bg-white even:bg-slate-50/70 border-b border-slate-200">
                            <td class="py-3.5 px-4 border-r border-slate-200">
                                <a href="{{ route('alkes.show', $log->alkes_id) }}" class="font-extrabold text-slate-900 hover:text-emerald-700 transition block text-sm">
                                    {{ $log->alkes->nama_barang ?? 'Alkes' }}
                                </a>
                                <div class="text-xs text-slate-500 font-mono font-bold mt-0.5">SN: {{ $log->alkes->nomor_seri ?? '-' }}</div>
                                <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-800 rounded-md text-[11px] font-bold border border-slate-300">
                                        {{ $log->alkes->ruangan->nama_ruangan ?? 'RS' }}
                                    </span>
                                    <span class="px-2 py-0.5 bg-amber-50 text-amber-800 rounded-md text-[11px] font-bold border border-amber-200">
                                        {{ $log->jenis_tindakan }}
                                    </span>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200 whitespace-nowrap">
                                <div class="text-xs font-bold text-slate-900">
                                    <span class="text-slate-500 font-semibold block text-[10px] uppercase">Waktu Lapor:</span>
                                    {{ $log->tanggal_mulai ? $log->tanggal_mulai->format('d M Y, H:i') : '-' }} WIB
                                </div>
                                <div class="text-xs font-bold mt-2 text-slate-900">
                                    <span class="text-slate-500 font-semibold block text-[10px] uppercase">Waktu Selesai:</span>
                                    @if ($log->tanggal_selesai)
                                        <span class="text-emerald-700 font-black">{{ $log->tanggal_selesai->format('d M Y, H:i') }} WIB</span>
                                    @else
                                        <span class="text-amber-600 italic font-semibold">Sedang Diproses</span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200 max-w-[280px]">
                                <div class="text-xs font-medium text-slate-900 leading-relaxed whitespace-pre-line">
                                    {{ $log->deskripsi_kerusakan ?: '-' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4 border-r border-slate-200 max-w-[260px]">
                                <div class="text-xs font-medium text-slate-800 leading-relaxed">
                                    {{ $log->tindakan_perbaikan ?: '-' }}
                                </div>
                                @if ($log->pelaksana_vendor)
                                    <div class="text-[10px] font-bold text-slate-500 mt-1">Teknisi/Vendor: {{ $log->pelaksana_vendor }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                @if ($log->status_hasil !== 'Selesai')
                                    @if ($currentRole === 'elektromedis')
                                        <button type="button" class="btn-resolve px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5 mx-auto shadow-sm"
                                            data-id="{{ $log->id }}"
                                            data-nama="{{ e($log->alkes->nama_barang ?? 'Alkes') }}"
                                            data-gejala="{{ e($log->deskripsi_kerusakan ?? '') }}">
                                            <i class="ri-check-double-line text-sm"></i>
                                            <span>Selesaikan</span>
                                        </button>
                                    @else
                                        <span class="px-3 py-1 bg-amber-100 text-amber-900 border border-amber-300 rounded-full text-xs font-extrabold inline-block">
                                            Proses Elektromedis
                                        </span>
                                    @endif
                                @else
                                    <div class="space-y-1">
                                        <span class="px-3 py-1 bg-emerald-100 text-emerald-900 border border-emerald-300 rounded-full text-xs font-black inline-flex items-center gap-1">
                                            <i class="ri-checkbox-circle-fill text-emerald-600"></i> Selesai
                                        </span>
                                        <button type="button" class="btn-detail text-[11px] font-bold text-emerald-700 hover:underline block mx-auto"
                                            data-log="{{ json_encode($log) }}"
                                            data-nama="{{ e($log->alkes->nama_barang ?? 'Alkes') }}"
                                            data-ruangan="{{ e($log->alkes->ruangan->nama_ruangan ?? 'RS') }}">
                                            Detail & Catatan &rarr;
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-700 font-bold">
                                <i class="ri-tools-line text-5xl block mb-2 text-slate-400"></i>
                                Tidak ada data perbaikan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 bg-slate-100/70 border-t border-slate-200">
            {{ $logList->links('pagination.custom') }}
        </div>
    </div>

</div>

<div id="resolveRepairModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-300 overflow-hidden animate-fade-in">
        <div class="px-6 py-4 bg-emerald-950 text-white flex items-center justify-between">
            <h4 class="font-bold text-base flex items-center gap-2">
                <i class="ri-tools-line text-amber-300"></i>
                Konfirmasi Perbaikan Selesai
            </h4>
            <button type="button" onclick="closeResolveModal()" class="text-slate-300 hover:text-white p-1 rounded-lg transition">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        <form id="resolveRepairForm" method="POST" action="" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Alat Kesehatan</label>
                <input type="text" id="modalResolveNamaAlkes" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-sm font-bold text-slate-900" readonly>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Gejala Awal dari Ruangan</label>
                <textarea id="modalResolveGejala" rows="2" class="w-full px-4 py-2 bg-slate-100 border border-slate-300 rounded-xl text-xs font-medium text-slate-800" readonly></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase mb-1.5">Diagnosa / Kerusakan Sebenarnya <span class="text-rose-600">*</span></label>
                <textarea name="diagnosa_kerusakan" rows="3" required placeholder="Tuliskan diagnosa teknis Elektromedis (misal: jarum patah, modul sensor O2 rusak, mainboard konslet)..." class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase mb-1.5">Tindakan Perbaikan yang Dilakukan <span class="text-rose-600">*</span></label>
                <textarea name="tindakan_perbaikan" rows="3" required placeholder="Tuliskan tindakan perbaikan yang telah dilakukan (misal: penggantian jarum baru, perbaikan sekring, pengujian fungsi)..." class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-medium text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase mb-1.5">Pelaksana / Vendor</label>
                <input type="text" name="pelaksana_vendor" value="Teknisi Elektromedis RS" class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600">
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                <button type="button" onclick="closeResolveModal()" class="px-5 py-2.5 bg-slate-100 text-slate-800 hover:bg-slate-200 rounded-xl text-xs font-bold transition">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/30 transition flex items-center gap-1.5">
                    <i class="ri-check-double-line"></i> Simpan & Kembalikan Alkes
                </button>
            </div>
        </form>
    </div>
</div>

<div id="detailRepairModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-300 overflow-hidden animate-fade-in">
        <div class="px-6 py-4 bg-emerald-950 text-white flex items-center justify-between">
            <h4 class="font-bold text-base flex items-center gap-2">
                <i class="ri-file-list-3-line text-amber-300"></i>
                Detail Log Perbaikan Alkes
            </h4>
            <button type="button" onclick="closeDetailModal()" class="text-slate-300 hover:text-white p-1 rounded-lg transition">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        <div class="p-6 space-y-4 text-sm">
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Unit Alkes</span>
                        <h5 id="detailNamaAlkes" class="font-black text-slate-900 text-base"></h5>
                    </div>
                    <span id="detailRuangan" class="px-2.5 py-1 bg-white text-slate-800 rounded-lg text-xs font-bold border border-slate-300"></span>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <span class="text-xs font-bold text-slate-700 uppercase block mb-1">Catatan & Diagnosa Teknis:</span>
                    <div id="detailCatatan" class="p-3 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-900 whitespace-pre-line leading-relaxed"></div>
                </div>

                <div id="detailFotoContainer" class="hidden mt-3">
                    <span class="text-xs font-bold text-slate-700 uppercase block mb-1">Foto Kondisi / Kerusakan:</span>
                    <img id="detailFotoImg" src="" alt="Foto Kerusakan" class="w-full max-h-48 object-cover rounded-xl border border-slate-300 shadow-sm">
                </div>

                <div>
                    <span class="text-xs font-bold text-slate-700 uppercase block mb-1">Tindakan Perbaikan Elektromedis:</span>
                    <div id="detailTindakan" class="p-3 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-900 leading-relaxed"></div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end">
                <button type="button" onclick="closeDetailModal()" class="px-5 py-2 bg-slate-100 text-slate-800 hover:bg-slate-200 rounded-xl text-xs font-bold transition">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    function openResolveModal(id, namaAlkes, gejala) {
        document.getElementById('resolveRepairForm').action = '/pemeliharaan/' + id + '/selesai';
        document.getElementById('modalResolveNamaAlkes').value = namaAlkes;
        document.getElementById('modalResolveGejala').value = gejala || '-';
        document.getElementById('resolveRepairModal').classList.remove('hidden');
    }

    function closeResolveModal() {
        document.getElementById('resolveRepairModal').classList.add('hidden');
    }

    function openDetailModal(log, namaAlkes, ruangan) {
        document.getElementById('detailNamaAlkes').innerText = namaAlkes;
        document.getElementById('detailRuangan').innerText = ruangan;
        document.getElementById('detailCatatan').innerText = log.deskripsi_kerusakan || '-';
        document.getElementById('detailTindakan').innerText = log.tindakan_perbaikan || '-';

        var photoContainer = document.getElementById('detailFotoContainer');
        var photoImg = document.getElementById('detailFotoImg');
        if (log.foto_kerusakan) {
            photoImg.src = log.foto_kerusakan;
            photoContainer.classList.remove('hidden');
        } else {
            photoContainer.classList.add('hidden');
            photoImg.src = '';
        }

        document.getElementById('detailRepairModal').classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            var resolveBtn = e.target.closest('.btn-resolve');
            if (resolveBtn) {
                var id = resolveBtn.getAttribute('data-id');
                var nama = resolveBtn.getAttribute('data-nama');
                var gejala = resolveBtn.getAttribute('data-gejala');
                openResolveModal(id, nama, gejala);
                return;
            }

            var detailBtn = e.target.closest('.btn-detail');
            if (detailBtn) {
                try {
                    var logData = JSON.parse(detailBtn.getAttribute('data-log') || '{}');
                    var namaAlkes = detailBtn.getAttribute('data-nama');
                    var ruanganName = detailBtn.getAttribute('data-ruangan');
                    openDetailModal(logData, namaAlkes, ruanganName);
                } catch (err) {
                    console.error('Failed to parse log data:', err);
                }
                return;
            }
        });
    });

    function closeDetailModal() {
        document.getElementById('detailRepairModal').classList.add('hidden');
    }
</script>
@endsection
