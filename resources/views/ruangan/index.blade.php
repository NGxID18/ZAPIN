@extends('layouts.app')

@section('title', 'Daftar Ruangan Rumah Sakit')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <i class="ri-building-4-line text-emerald-600"></i>
                Daftar Ruangan Rumah Sakit
            </h3>
            <p class="text-sm text-slate-700 mt-1 font-medium">Monitoring daftar unit instalasi & ruangan di RSJKO Engku Haji Daud</p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <div class="relative flex-1 md:w-72">
                <input type="text" id="searchRuanganInput" placeholder="Cari nama ruangan..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 shadow-xs transition">
                <i class="ri-search-line absolute left-3.5 top-3 text-slate-400"></i>
            </div>
        </div>
    </div>

    <div class="max-w-xs">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/90 border-l-4 border-l-teal-600 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-2xl font-bold shrink-0">
                <i class="ri-building-line"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-teal-800 uppercase tracking-wider">Total Ruangan</p>
                <h3 class="text-2xl font-black text-slate-900 mt-0.5">{{ number_format($totalRuangan) }} <span class="text-xs font-semibold text-slate-600">Unit</span></h3>
            </div>
        </div>
    </div>

    @php
        $bgColors = [
            'bg-emerald-50 text-emerald-700 border-emerald-200',
            'bg-teal-50 text-teal-700 border-teal-200',
            'bg-indigo-50 text-indigo-700 border-indigo-200',
            'bg-amber-50 text-amber-700 border-amber-200',
            'bg-cyan-50 text-cyan-700 border-cyan-200',
            'bg-purple-50 text-purple-700 border-purple-200',
        ];
    @endphp

    <div id="roomCardGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach ($ruanganList as $index => $ruang)
            @php
                $colorClass = $bgColors[$index % count($bgColors)];
                $totalUnit = $ruang->alkes_count;
                $baikCount = $ruang->alkes_baik_count;
                $rusakCount = $ruang->alkes_rusak_count;
                $baikPct = $totalUnit > 0 ? round(($baikCount / $totalUnit) * 100) : 100;
            @endphp

            <a href="{{ route('alkes.index', ['lokasi_ruangan_id' => $ruang->id]) }}" class="room-card bg-white p-5 rounded-2xl border border-slate-200/90 hover:border-emerald-500 hover:shadow-lg transition-all duration-200 group flex flex-col justify-between space-y-4" data-name="{{ strtolower($ruang->nama_ruangan) }}">
                
                <div class="space-y-3">
                    <div class="w-10 h-10 rounded-xl {{ $colorClass }} border flex items-center justify-center text-lg font-bold shrink-0 group-hover:scale-105 transition-transform">
                        <i class="ri-hospital-line"></i>
                    </div>

                    <div>
                        <h4 class="font-extrabold text-slate-900 text-base group-hover:text-emerald-700 transition leading-snug">{{ $ruang->nama_ruangan }}</h4>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                            <i class="ri-checkbox-circle-fill text-emerald-600 text-xs"></i> {{ $baikCount }} Baik
                        </span>
                        @if ($rusakCount > 0)
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-800 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                                <i class="ri-tools-fill text-rose-600 text-xs"></i> {{ $rusakCount }} Rusak
                            </span>
                        @endif
                    </div>

                    @if ($totalUnit > 0)
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden flex">
                            <div class="bg-emerald-500 h-full" style="width: {{ $baikPct }}%"></div>
                            @if ($rusakCount > 0)
                                <div class="bg-rose-500 h-full" style="width: {{ 100 - $baikPct }}%"></div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-black text-slate-900 bg-slate-100 px-3 py-1 rounded-xl border border-slate-200 group-hover:border-emerald-300 group-hover:bg-emerald-50 group-hover:text-emerald-800 transition">
                        {{ number_format($totalUnit) }} Unit Alkes
                    </span>
                    <span class="text-xs font-bold text-emerald-700 group-hover:translate-x-1 transition flex items-center gap-0.5">
                        Inventaris <i class="ri-arrow-right-s-line text-sm"></i>
                    </span>
                </div>

            </a>
        @endforeach
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchRuanganInput');
    const cards = document.querySelectorAll('.room-card');

    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase().trim();
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name.includes(query)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
</script>
@endsection
