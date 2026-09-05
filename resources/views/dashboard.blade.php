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
                <p class="text-xs text-emerald-200 mt-0.5 font-medium">Zona Aplikasi Pengelolaan Inventaris Alat Kesehatan RSJKO Engku Haji Daud &middot; Aktif sebagai <span class="font-bold text-amber-300">{{ session('user_role_label', 'Instalasi Elektromedis') }}</span></p>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('welcomeBanner').remove()" class="text-emerald-200 hover:text-white p-1.5 rounded-lg transition" title="Tutup">
            <i class="ri-close-line text-xl"></i>
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

        <a href="{{ route('alkes.index') }}" class="bg-white p-6 rounded-2xl border border-slate-200/90 border-l-4 border-l-teal-600 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-teal-800 uppercase tracking-wider">Total Unit Alkes</p>
                <h3 class="text-4xl font-black text-slate-900 mt-2 tracking-tight group-hover:text-teal-600 transition-colors">{{ number_format($totalAlkes) }}</h3>
                <p class="text-xs text-slate-700 mt-1 font-semibold flex items-center gap-1.5">
                    <i class="ri-hospital-line text-teal-600"></i> Terdata di RSJKO EHD
                </p>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-3xl group-hover:bg-teal-600 group-hover:text-white transition-all duration-200 shadow-sm shrink-0">
                <i class="ri-stethoscope-line"></i>
            </div>
        </a>

        <a href="{{ route('alkes.index', ['kondisi' => 'BAIK']) }}" class="bg-white p-6 rounded-2xl border border-slate-200/90 border-l-4 border-l-emerald-600 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Unit Operasional (Baik)</p>
                <h3 class="text-4xl font-black text-emerald-700 mt-2 tracking-tight">{{ number_format($alkesTersedia) }}</h3>
                <p class="text-xs text-slate-700 mt-1 font-semibold flex items-center gap-1.5">
                    <i class="ri-checkbox-circle-fill text-emerald-600"></i> Kondisi Baik & Aktif
                </p>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-3xl group-hover:bg-emerald-600 group-hover:text-white transition-all duration-200 shadow-sm shrink-0">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </a>

        <a href="{{ route('pemeliharaan.index') }}" class="bg-white p-6 rounded-2xl border border-slate-200/90 border-l-4 border-l-rose-600 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-rose-800 uppercase tracking-wider">Unit Perlu Perbaikan</p>
                <h3 class="text-4xl font-black text-rose-700 mt-2 tracking-tight">{{ number_format($alkesRusak) }}</h3>
                <p class="text-xs text-rose-800 font-bold mt-1 flex items-center gap-1.5">
                    <i class="ri-tools-line text-rose-600"></i> Dalam Perbaikan / Kalibrasi
                </p>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-3xl group-hover:bg-rose-600 group-hover:text-white transition-all duration-200 shadow-sm shrink-0">
                <i class="ri-error-warning-line"></i>
            </div>
        </a>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-stretch">

        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-200">
                <h4 class="font-extrabold text-slate-900 text-base flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-lg">
                        <i class="ri-pie-chart-2-line"></i>
                    </div>
                    Kondisi Aset Alkes RS
                </h4>
                <span class="text-xs font-bold px-3 py-1 bg-slate-100 text-slate-800 rounded-lg border border-slate-200">Proporsi Total</span>
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
            labels: {!! json_encode($chartRuanganLabels) !!},
            datasets: [
                {
                    label: 'Baik / Operasional',
                    data: {!! json_encode($chartKondisiBaik) !!},
                    backgroundColor: '#059669',
                    borderRadius: 5
                },
                {
                    label: 'Rusak / Perbaikan',
                    data: {!! json_encode($chartKondisiRusak) !!},
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
