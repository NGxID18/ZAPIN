@extends('layouts.app')

@section('title', 'Dashboard Inventaris Alkes')

@section('content')
<div class="space-y-6">

    @php
        $currentRole = session('user_role', 'elektromedis');
    @endphp

    <div id="welcomeBanner" class="px-6 py-5 bg-gradient-to-r from-emerald-950 via-emerald-900 to-slate-900 text-white rounded-2xl border border-emerald-800 shadow-lg flex items-center justify-between animate-fade-in">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-400/20 text-amber-300 border border-amber-400/30 flex items-center justify-center text-2xl shrink-0">
                <i class="ri-hospital-line"></i>
            </div>
            <div>
                <p class="font-extrabold text-base text-white tracking-tight">Selamat Datang di ZAPIN</p>
                <p class="text-xs text-emerald-200 mt-0.5 font-medium">Masuk sebagai <span class="font-bold text-amber-300">{{ session('user_role_label', 'Instalasi Elektromedis') }}</span></p>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('welcomeBanner').remove()" class="text-emerald-200 hover:text-white p-1.5 rounded-lg transition" title="Tutup">
            <i class="ri-close-line text-xl"></i>
        </button>
    </div>

    <!-- Executive Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <!-- 1. Total Unit Alkes -->
        <a href="{{ route('alkes.index') }}" class="bg-white p-5 rounded-2xl border border-slate-200/90 border-l-4 border-l-teal-600 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group flex items-center justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold text-teal-800 uppercase tracking-wider">Total Unit Alkes</p>
                <h3 class="text-3xl font-black text-slate-900 mt-1.5 tracking-tight group-hover:text-teal-600 transition-colors">{{ number_format($totalAlkes) }}</h3>
                <p class="text-xs text-slate-700 mt-1 font-semibold flex items-center gap-1.5 truncate">
                    <i class="ri-hospital-line text-teal-600"></i> Tersebar di 25 Ruangan RS
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-2xl group-hover:bg-teal-600 group-hover:text-white transition-all duration-200 shadow-sm shrink-0">
                <i class="ri-stethoscope-line"></i>
            </div>
        </a>

        <!-- 2. Total Valuasi Aset Terdata -->
        <a href="{{ route('alkes.index', ['sort_by' => 'nilai_perolehan', 'sort_dir' => 'desc']) }}" class="bg-white p-5 rounded-2xl border border-slate-200/90 border-l-4 border-l-indigo-600 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group flex items-center justify-between" title="{{ $totalNilaiAsetFull }} (Akumulasi Nilai Tercatat)">
            <div class="min-w-0">
                <p class="text-xs font-bold text-indigo-800 uppercase tracking-wider">Total Valuasi Aset</p>
                <h3 class="text-3xl font-black text-indigo-700 mt-1.5 tracking-tight group-hover:text-indigo-600 transition-colors">{{ $totalNilaiAsetFormatted }}</h3>
                <p class="text-xs text-slate-700 mt-1 font-semibold flex items-center gap-1.5 truncate">
                    <i class="ri-funds-line text-indigo-600"></i> {{ $unitBernilai }} Unit Memiliki Nilai
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl group-hover:bg-indigo-600 group-hover:text-white transition-all duration-200 shadow-sm shrink-0">
                <i class="ri-wallet-3-line"></i>
            </div>
        </a>

        <!-- 3. Valuasi Operasional (Baik) -->
        <a href="{{ route('alkes.index', ['kondisi' => 'BAIK']) }}" class="bg-white p-5 rounded-2xl border border-slate-200/90 border-l-4 border-l-emerald-600 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group flex items-center justify-between" title="Nilai Operasional: Rp {{ number_format($nilaiBaik, 0, ',', '.') }}">
            <div class="min-w-0">
                <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Aset Operasional (Baik)</p>
                <h3 class="text-3xl font-black text-emerald-700 mt-1.5 tracking-tight">{{ $nilaiBaikFormatted }}</h3>
                <p class="text-xs text-slate-700 mt-1 font-semibold flex items-center gap-1.5 truncate">
                    <i class="ri-checkbox-circle-fill text-emerald-600"></i> {{ number_format($alkesTersedia) }} Unit ({{ $nilaiBaikPersen }}% Valuasi)
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl group-hover:bg-emerald-600 group-hover:text-white transition-all duration-200 shadow-sm shrink-0">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </a>

        <!-- 4. Valuasi Perlu Perbaikan -->
        <a href="{{ route('pemeliharaan.index') }}" class="bg-white p-5 rounded-2xl border border-slate-200/90 border-l-4 border-l-rose-600 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group flex items-center justify-between" title="Nilai Tertahan: Rp {{ number_format($nilaiRusak, 0, ',', '.') }}">
            <div class="min-w-0">
                <p class="text-xs font-bold text-rose-800 uppercase tracking-wider">Aset Perlu Perbaikan</p>
                <h3 class="text-3xl font-black text-rose-700 mt-1.5 tracking-tight">{{ $nilaiRusakFormatted }}</h3>
                <p class="text-xs text-rose-800 font-bold mt-1 flex items-center gap-1.5 truncate">
                    <i class="ri-error-warning-fill text-rose-600"></i> {{ number_format($alkesRusak) }} Unit ({{ $nilaiRusakPersen }}% Valuasi)
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl group-hover:bg-rose-600 group-hover:text-white transition-all duration-200 shadow-sm shrink-0">
                <i class="ri-error-warning-line"></i>
            </div>
        </a>

    </div>

    <!-- Financial Portfolio & Capital Sources Section (Bento Grid) -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200 gap-3">
            <div>
                <h4 class="font-extrabold text-slate-900 text-base flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                        <i class="ri-pie-chart-box-line"></i>
                    </div>
                    Komposisi Portofolio Sumber Perolehan & Valuasi Aset
                </h4>
                <p class="text-xs text-slate-700 font-medium mt-1">Distribusi alokasi pendanaan alat kesehatan berdasarkan sumber anggaran resmi RSJKO EHD</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold px-3 py-1.5 bg-indigo-50 text-indigo-800 rounded-xl border border-indigo-200 flex items-center gap-1.5">
                    <i class="ri-shield-check-line text-indigo-600"></i>
                    Portofolio: <span class="font-black">{{ $totalNilaiAsetFormatted }}</span>
                </span>
            </div>
        </div>

        <!-- Segmented Portfolio Allocation Bar -->
        <div class="space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                <span class="flex items-center gap-1.5">
                    <i class="ri-bar-chart-horizontal-line text-indigo-600"></i> Alokasi Modal Anggaran (Capital Allocation)
                </span>
                <span class="text-[11px] font-semibold text-slate-500">100% dari {{ $totalNilaiAsetFull }}</span>
            </div>
            <div class="w-full h-3.5 bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                @foreach ($sumberStats as $key => $s)
                    @if ($s['persen'] > 0)
                        <div class="{{ $s['bar_bg'] }} transition-all duration-500 relative group cursor-pointer" 
                             style="width: {{ $s['persen'] }}%" 
                             title="{{ $s['label'] }}: {{ $s['full_value'] }} ({{ $s['persen'] }}%)">
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Two Column Bento: Left Sources, Right Top Valued Assets -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Sumber Perolehan Cards Grid (7 Cols) -->
            <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                @foreach ($sumberStats as $key => $s)
                    <a href="{{ $s['query'] ? route('alkes.index', ['search' => $s['query']]) : route('alkes.index') }}" 
                       class="p-4 rounded-xl border border-slate-200/90 hover:border-indigo-400 hover:shadow-md transition-all duration-200 bg-slate-50/60 hover:bg-white group flex flex-col justify-between">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center text-sm {{ $s['bg'] }}">
                                    <i class="{{ $s['icon'] }}"></i>
                                </div>
                                <span class="text-xs font-extrabold text-slate-900 group-hover:text-indigo-600 transition truncate">{{ $s['badge'] }}</span>
                            </div>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-md {{ $s['bg'] }}">
                                {{ $s['persen'] }}%
                            </span>
                        </div>

                        <div class="mt-3">
                            <h5 class="text-xl font-black text-slate-900 tracking-tight group-hover:text-indigo-600 transition">{{ $s['formatted_value'] }}</h5>
                            <div class="flex items-center justify-between text-[11px] text-slate-600 mt-1 font-medium">
                                <span>{{ $s['count'] }} Unit Alat</span>
                                <span class="text-indigo-600 font-bold opacity-0 group-hover:opacity-100 transition flex items-center gap-0.5">
                                    Lihat <i class="ri-arrow-right-s-line"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Top Valued Assets Ranked Ledger (5 Cols) -->
            <div class="lg:col-span-5 bg-slate-50/80 rounded-xl border border-slate-200/90 p-4 space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-200">
                    <h5 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="ri-award-line text-amber-500 text-base"></i>
                        Top 5 Aset Bernilai Tertinggi
                    </h5>
                    <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Investasi Utama</span>
                </div>

                <div class="space-y-2.5">
                    @foreach ($topValuedAlkes as $idx => $tAlkes)
                        <a href="{{ route('alkes.show', $tAlkes->id) }}" class="p-3 bg-white rounded-lg border border-slate-200 hover:border-emerald-500 hover:shadow-sm transition-all duration-150 flex items-center justify-between gap-3 group">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 font-black text-xs flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition">
                                    {{ $idx + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <h6 class="font-extrabold text-xs text-slate-900 group-hover:text-emerald-700 transition truncate">
                                        {{ $tAlkes->nama_barang }}
                                    </h6>
                                    <p class="text-[10px] text-slate-500 font-medium truncate flex items-center gap-1 mt-0.5">
                                        <span>{{ $tAlkes->ruangan?->nama_ruangan ?? '-' }}</span>
                                        <span>•</span>
                                        <span>{{ $tAlkes->cara_perolehan ?: 'Pengadaan RS' }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-black text-slate-900 group-hover:text-emerald-700 transition">
                                    Rp {{ number_format($tAlkes->numeric_value, 0, ',', '.') }}
                                </p>
                                <span class="text-[9px] font-extrabold px-1.5 py-0.5 rounded {{ strtoupper($tAlkes->kondisi) === 'BAIK' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    {{ $tAlkes->kondisi ?: 'UNKNOWN' }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    <!-- Charts Section (Kondisi Aset & Sebaran per Ruangan) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-stretch">

        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-200">
                <h4 class="font-extrabold text-slate-900 text-base flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-lg">
                        <i class="ri-pie-chart-2-line"></i>
                    </div>
                    Kondisi Fisik Unit Alkes RS
                </h4>
                <span class="text-xs font-bold px-3 py-1 bg-slate-100 text-slate-800 rounded-lg border border-slate-200">Proporsi Unit</span>
            </div>
            <div class="relative h-60 flex items-center justify-center">
                <canvas id="chartStatusKondisi"></canvas>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-200">
                <h4 class="font-extrabold text-slate-900 text-base flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                        <i class="ri-bar-chart-grouped-line"></i>
                    </div>
                    Kondisi Alkes per Ruangan
                </h4>
                <span class="text-xs font-bold px-3 py-1 bg-slate-100 text-slate-800 rounded-lg border border-slate-200">Per Ruangan</span>
            </div>
            <div class="relative h-60">
                <canvas id="chartRuanganKondisi"></canvas>
            </div>
        </div>

    </div>

    <!-- Sebaran Alkes per Ruangan (25 Ruangan Grid) -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200 gap-2">
            <div>
                <h4 class="font-extrabold text-slate-900 text-base flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="ri-building-4-line"></i>
                    </div>
                    Sebaran Unit Alkes per Ruangan
                </h4>
                <p class="text-xs text-slate-700 font-medium mt-1">Distribusi aset alat kesehatan aktif di setiap unit instalasi/ruangan</p>
            </div>
            <a href="{{ route('ruangan.index') }}" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-600 text-emerald-800 hover:text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 border border-emerald-200">
                <span>Lihat Semua Ruangan</span>
                <i class="ri-arrow-right-line"></i>
            </a>
        </div>

        @php
            $bgColors = [
                'bg-emerald-50 text-emerald-700 hover:bg-emerald-600',
                'bg-teal-50 text-teal-700 hover:bg-teal-600',
                'bg-indigo-50 text-indigo-700 hover:bg-indigo-600',
                'bg-amber-50 text-amber-700 hover:bg-amber-600',
                'bg-cyan-50 text-cyan-700 hover:bg-cyan-600',
                'bg-purple-50 text-purple-700 hover:bg-purple-600',
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($ruanganList as $index => $r)
                @php $colorClass = $bgColors[$index % count($bgColors)]; @endphp
                <a href="{{ route('alkes.index', ['lokasi_ruangan_id' => $r->id]) }}" class="bg-slate-50 hover:bg-white p-4 rounded-xl border border-slate-200 hover:border-emerald-500 hover:shadow-md transition-all duration-200 group flex flex-col justify-between" title="Klik untuk lihat daftar alkes yang berada di ruangan ini">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-600 block">Ruangan</span>
                            <h5 class="font-extrabold text-slate-900 text-sm group-hover:text-emerald-700 transition truncate mt-0.5">{{ $r->nama_ruangan }}</h5>
                        </div>
                        <div class="w-8 h-8 rounded-lg {{ explode(' hover:', $colorClass)[0] }} flex items-center justify-center text-sm font-bold shrink-0 group-hover:text-white transition">
                            <i class="ri-hospital-line"></i>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs">
                        <span class="text-slate-700 font-bold">Jumlah Unit:</span>
                        <span class="font-black text-slate-900 bg-white px-2.5 py-0.5 rounded-lg border border-slate-300 group-hover:border-emerald-400 group-hover:text-emerald-700 transition">
                            {{ $r->alkes_count }} Unit
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctxKondisi = document.getElementById('chartStatusKondisi').getContext('2d');
    new Chart(ctxKondisi, {
        type: 'doughnut',
        data: {
            labels: ['Operasional / Baik', 'Dalam Perbaikan / Rusak'],
            datasets: [{
                data: [{{ $alkesTersedia }}, {{ $alkesRusak }}],
                backgroundColor: ['#059669', '#e11d48'],
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        font: { family: 'Inter', size: 12, weight: '700' },
                        color: '#0f172a',
                        padding: 16
                    }
                }
            },
            cutout: '70%'
        }
    });

    const ctxRuangan = document.getElementById('chartRuanganKondisi').getContext('2d');
    new Chart(ctxRuangan, {
        type: 'bar',
        data: {
            labels: @js($chartRuanganLabels),
            datasets: [
                {
                    label: 'Baik / Operasional',
                    data: @js($chartKondisiBaik),
                    backgroundColor: '#059669',
                    borderRadius: 5
                },
                {
                    label: 'Rusak / Perbaikan',
                    data: @js($chartKondisiRusak),
                    backgroundColor: '#e11d48',
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Inter', size: 10, weight: '600' }, color: '#334155' } },
                y: { stacked: true, beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { font: { family: 'Inter', size: 10, weight: '600' }, color: '#334155' } }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, font: { family: 'Inter', size: 12, weight: '700' }, color: '#0f172a' }
                }
            }
        }
    });
});
</script>
@endsection
