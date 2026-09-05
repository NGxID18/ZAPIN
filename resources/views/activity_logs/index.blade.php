@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <i class="ri-history-line text-emerald-600"></i>
                Log Aktivitas & Audit Trail Sistem
            </h3>
            <p class="text-sm text-slate-700 mt-1 font-medium">Pelacakan otomatis seluruh aktivitas pengguna, perubahan data, dan transaksi aset</p>
        </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
        <form method="GET" action="{{ route('activity-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5 uppercase">Cari Deskripsi / Pengguna / Ruangan</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="w-full pl-10 pr-4 h-11 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition">
                    <i class="ri-search-line absolute left-3.5 top-3 text-slate-400 text-base"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5 uppercase">Tipe Aktivitas</label>
                <select name="action" class="w-full">
                    <option value="">-- Semua Aktivitas --</option>
                    @foreach ($actionTypes as $type)
                        <option value="{{ $type }}" {{ request('action') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-3 justify-end">
                <button type="submit" class="h-11 px-6 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition flex items-center gap-2 shrink-0">
                    <i class="ri-search-line"></i> Cari
                </button>
                @if (request()->hasAny(['search', 'action']))
                    <a href="{{ route('activity-logs.index') }}" class="h-11 w-11 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl border border-slate-300 transition flex items-center justify-center shrink-0" title="Reset">
                        <i class="ri-refresh-line text-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-300 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-emerald-950 text-white border-b border-emerald-900 text-xs font-black uppercase tracking-wider">
                        <th class="px-4 py-3.5 border-r border-emerald-900">Waktu & Tanggal</th>
                        <th class="px-4 py-3.5 border-r border-emerald-900">Peran & Ruangan</th>
                        <th class="px-4 py-3.5 border-r border-emerald-900">Aktivitas</th>
                        <th class="px-4 py-3.5 border-r border-emerald-900">Deskripsi Perubahan</th>
                        <th class="px-4 py-3.5 text-center">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm font-medium text-slate-900">
                    @forelse ($logs as $log)
                        @php
                            $actionLower = strtolower($log->action);
                            $badgeColor = 'bg-slate-100 text-slate-900 border-slate-300';
                            if (str_contains($actionLower, 'tambah') || str_contains($actionLower, 'create')) {
                                $badgeColor = 'bg-emerald-100 text-emerald-900 border-emerald-300';
                            } elseif (str_contains($actionLower, 'edit') || str_contains($actionLower, 'update')) {
                                $badgeColor = 'bg-blue-100 text-blue-900 border-blue-300';
                            } elseif (str_contains($actionLower, 'hapus') || str_contains($actionLower, 'delete')) {
                                $badgeColor = 'bg-rose-100 text-rose-900 border-rose-300';
                            } elseif (str_contains($actionLower, 'mutasi') || str_contains($actionLower, 'pindah')) {
                                $badgeColor = 'bg-indigo-100 text-indigo-900 border-indigo-300';
                            } elseif (str_contains($actionLower, 'perbaikan') || str_contains($actionLower, 'lapor')) {
                                $badgeColor = 'bg-amber-100 text-amber-900 border-amber-300';
                            }
                        @endphp
                        <tr class="hover:bg-emerald-50/40 transition odd:bg-white even:bg-slate-50/70 border-b border-slate-200">
                            <td class="px-4 py-3.5 whitespace-nowrap border-r border-slate-200">
                                <div class="font-bold text-slate-900 text-sm">{{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</div>
                                <div class="text-xs text-slate-500 font-mono font-bold mt-0.5">{{ $log->created_at->timezone('Asia/Jakarta')->format('H:i:s') }} WIB</div>
                            </td>

                            <td class="px-4 py-3.5 border-r border-slate-200">
                                <div class="font-extrabold text-slate-900 text-sm">{{ $log->user_role }}</div>
                                <div class="text-xs text-slate-600 font-bold mt-0.5">{{ $log->ruangan_name ?? 'Pusat' }}</div>
                            </td>

                            <td class="px-4 py-3.5 border-r border-slate-200">
                                <span class="px-3 py-1 rounded-lg text-xs font-black border {{ $badgeColor }}">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5 border-r border-slate-200">
                                <p class="text-xs text-slate-900 font-semibold leading-relaxed">{{ $log->description }}</p>
                            </td>

                            <td class="px-4 py-3.5 text-center font-mono text-xs font-bold text-slate-700">
                                {{ $log->ip_address }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-700 font-bold">
                                <i class="ri-history-line text-5xl block mb-3 text-slate-400"></i>
                                Belum ada catatan aktivitas tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 bg-slate-100/70 border-t border-slate-200">
            {{ $logs->links('pagination.custom') }}
        </div>
    </div>

</div>
@endsection
