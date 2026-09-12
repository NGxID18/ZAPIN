<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - ZAPIN RSJKO Engku Haji Daud</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <style>
        body, input, button, select, textarea { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        i[class^="ri-"]::before, i[class*=" ri-"]::before, [class^="ri-"]::before, [class*=" ri-"]::before {
            font-family: 'remixicon' !important;
            font-style: normal;
            -webkit-font-smoothing: antialiased;
        }

        #sidebarDrawer {
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #mainContentWrapper {
            transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #collapseBtnIcon {
            transition: transform 0.25s ease;
        }

        @media (min-width: 768px) {
            .sidebar-collapsed #sidebarDrawer { width: 4.5rem !important; }
            .sidebar-collapsed #mainContentWrapper { margin-left: 4.5rem !important; }
            .sidebar-collapsed .sidebar-text { display: none !important; }
            .sidebar-collapsed .sidebar-item {
                justify-content: center !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            .sidebar-collapsed #collapseBtnIcon { transform: rotate(180deg); }
        }

        .ts-wrapper { border-radius: 0.625rem !important; width: 100% !important; }
        .ts-control {
            border-radius: 0.625rem !important;
            background-color: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            padding: 0.55rem 0.875rem !important;
            min-height: 44px !important;
            display: flex !important;
            align-items: center !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #0f172a !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: all 0.15s ease !important;
        }
        .ts-control .item { font-size: 0.875rem !important; font-weight: 600 !important; color: #0f172a !important; }
        .ts-control > input { font-size: 0.875rem !important; font-weight: 500 !important; color: #0f172a !important; }
        .ts-wrapper.focus .ts-control {
            border-color: #059669 !important;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2) !important;
            background-color: #ffffff !important;
        }
        .ts-dropdown {
            border-radius: 0.625rem !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 10px 40px -8px rgba(0, 0, 0, 0.2) !important;
            overflow: hidden !important;
            z-index: 9999 !important;
            padding: 4px !important;
            background: #ffffff !important;
        }
        .ts-dropdown .option {
            padding: 9px 12px !important;
            border-radius: 0.375rem !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            color: #1e293b !important;
            transition: all 0.1s ease !important;
        }
        .ts-dropdown .option.active, .ts-dropdown .option:hover {
            background-color: #059669 !important;
            color: #ffffff !important;
        }

        #notificationDropdown {
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
            opacity: 0;
            transform: translateY(-8px);
            visibility: hidden;
            pointer-events: none;
        }
        #notificationDropdown.dropdown-active {
            opacity: 1;
            transform: translateY(0);
            visibility: visible;
            pointer-events: auto;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeInUp 0.25s ease-out; }

        .scrollbar-thin::-webkit-scrollbar { width: 4px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #475569; border-radius: 999px; }
    </style>

    <script>
        if (localStorage.getItem('zapin_sidebar_collapsed') === 'true' && window.innerWidth >= 768) {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>
</head>
<body id="appBody" class="h-full text-slate-900 antialiased flex flex-col min-h-screen bg-slate-100/80">

    <script>
        if (document.documentElement.classList.contains('sidebar-collapsed')) {
            document.body.classList.add('sidebar-collapsed');
        }
    </script>

    @php
        $currentRole = $currentRole ?? session('user_role', 'elektromedis');
        $userRoleLabel = $userRoleLabel ?? session('user_role_label', 'Instalasi Elektromedis');
        $unreadNotifCount = $unreadNotifCount ?? 0;
        $recentNotifs = $recentNotifs ?? collect();
    @endphp

    <div id="mobileSidebarOverlay" onclick="closeMobileSidebar()" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 hidden md:hidden transition-opacity duration-200"></div>

    <header class="fixed top-0 left-0 right-0 h-16 bg-emerald-950/85 backdrop-blur-md text-white border-b border-emerald-800/60 px-4 sm:px-6 flex items-center justify-between z-50 shadow-lg">
        
        <div class="flex items-center gap-3 z-10">
            <button type="button" onclick="openMobileSidebar()" class="md:hidden p-2 rounded-xl bg-emerald-900/80 text-white hover:bg-emerald-800 transition" title="Menu Navigasi">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-emerald-500 via-teal-500 to-amber-400 text-white flex items-center justify-center shadow-lg shadow-emerald-600/30 shrink-0 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35zM10.5 11h3v3h2v-3h3V9h-3V6h-2v3h-3v2z"/></svg>
                </div>
                <div class="block">
                    <h2 class="font-extrabold text-white text-xs sm:text-sm tracking-tight leading-tight group-hover:text-amber-300 transition">ZAPIN</h2>
                    <p class="text-[10px] sm:text-[11px] text-amber-300 font-bold leading-none mt-0.5 hidden sm:block">RSJKO Engku Haji Daud</p>
                </div>
            </a>
        </div>

        <div class="absolute left-1/2 -translate-x-1/2 text-center pointer-events-none z-10 hidden md:block">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-black tracking-[0.2em] text-white drop-shadow-md">
                <span class="bg-gradient-to-r from-amber-300 via-yellow-200 to-amber-400 bg-clip-text text-transparent">ZAPIN</span>
            </h1>
        </div>

        <div class="flex items-center gap-2 sm:gap-2.5 z-10 ml-auto">
            <div class="h-9 px-2.5 sm:px-3 py-1.5 bg-amber-400/20 text-amber-300 border border-amber-400/40 rounded-xl text-xs font-bold flex items-center gap-1.5 backdrop-blur-xs shrink-0 max-w-[130px] sm:max-w-[200px] md:max-w-none" title="{{ $userRoleLabel }}">
                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-5.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                <span class="truncate text-[10px] sm:text-xs">{{ $userRoleLabel }}</span>
            </div>

            @if ($currentRole === 'elektromedis')
                <div class="relative">
                    <button type="button" id="notifBellBtn" onclick="toggleNotificationDropdown()" class="h-9 px-3 py-1.5 rounded-xl bg-emerald-900/80 text-white hover:bg-emerald-800 transition focus:outline-none border border-emerald-800 flex items-center justify-center relative shrink-0" title="Notifikasi">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if ($unreadNotifCount > 0)
                            <span class="absolute -top-1 -right-1 w-4.5 h-4.5 bg-rose-500 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-emerald-950 animate-bounce">
                                {{ $unreadNotifCount }}
                            </span>
                        @endif
                    </button>

                    <div id="notificationDropdown" class="absolute right-0 mt-2.5 w-80 sm:w-96 bg-white text-slate-900 rounded-2xl border border-slate-200 shadow-2xl overflow-hidden z-50">
                        <div class="px-4 py-3 bg-emerald-950 text-white flex items-center justify-between">
                            <h4 class="font-bold text-sm flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                Notifikasi Masuk
                            </h4>
                            @if ($unreadNotifCount > 0)
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-amber-300 hover:underline font-semibold">Tandai Dibaca</button>
                                </form>
                            @endif
                        </div>

                        <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 scrollbar-thin">
                            @forelse ($recentNotifs as $n)
                                <div class="p-3.5 hover:bg-slate-50 transition {{ !$n->dibaca ? 'bg-amber-50/60' : '' }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-xs text-slate-900 truncate">{{ $n->judul }}</span>
                                        <span class="text-[10px] text-slate-500 shrink-0 font-medium">{{ $n->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs text-slate-700 mt-1 leading-relaxed">{{ $n->pesan }}</p>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500 text-center py-8 font-medium">Belum ada notifikasi laporan perbaikan.</p>
                            @endforelse
                        </div>

                        <div class="p-3 bg-slate-50 border-t border-slate-100 text-center">
                            <a href="{{ route('pemeliharaan.index') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-900">Lihat Semua Laporan &rarr;</a>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="h-9 px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-sm border border-rose-500 shrink-0" title="Keluar">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span class="hidden md:inline">Keluar</span>
                </button>
            </form>
        </div>
    </header>

    <div class="flex flex-1 pt-16 relative">
        <aside id="sidebarDrawer" class="fixed top-16 left-0 bottom-0 w-64 bg-emerald-950/85 backdrop-blur-md text-white border-r border-emerald-800/60 z-[45] transform -translate-x-full md:translate-x-0 shadow-lg">
            <button type="button" id="sidebarCollapseBtn" onclick="toggleSidebarCollapse()" class="hidden md:flex absolute -right-3.5 top-1/2 -translate-y-1/2 w-7 h-7 bg-emerald-600 hover:bg-emerald-500 text-white rounded-full items-center justify-center shadow-xl border-2 border-emerald-950 transition-all z-50 focus:outline-none" title="Ciutkan Sidebar">
                <svg id="collapseBtnIcon" class="w-4 h-4 text-white transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
            </button>

            <div class="w-full h-full flex flex-col justify-between overflow-y-auto overflow-x-hidden scrollbar-thin">
                <div>
                    <div class="px-4 py-3 flex items-center justify-between md:hidden border-b border-emerald-900">
                        <span class="text-xs font-bold text-amber-300 uppercase tracking-wider">Navigasi System</span>
                        <button type="button" onclick="closeMobileSidebar()" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-emerald-900">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <nav class="p-3 space-y-1 divide-y divide-emerald-900/60">
                        <div class="space-y-1 pb-1">
                            <div class="px-3 py-2 text-[11px] font-bold text-amber-300 uppercase tracking-wider sidebar-text">Menu Utama</div>

                            <a href="{{ route('dashboard') }}" onclick="closeMobileSidebar()" class="sidebar-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-slate-200 hover:bg-emerald-900 hover:text-white' }}">
                                <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                                <span class="sidebar-text">Dashboard</span>
                            </a>
                        </div>

                        <div class="space-y-1 pt-1.5 pb-1">
                            <a href="{{ route('mutasi.index') }}" onclick="closeMobileSidebar()" class="sidebar-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ request()->routeIs('mutasi.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-slate-200 hover:bg-emerald-900 hover:text-white' }}">
                                <svg class="w-5 h-5 text-indigo-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7h11m0 0l-4-4m4 4l-4 4m-3 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                <span class="sidebar-text">Pindah Ruangan</span>
                            </a>
                        </div>

                        <div class="space-y-1 pt-1.5 pb-1">
                            <a href="{{ route('peminjaman.index') }}" onclick="closeMobileSidebar()" class="sidebar-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ request()->routeIs('peminjaman.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-slate-200 hover:bg-emerald-900 hover:text-white' }}">
                                <svg class="w-5 h-5 text-sky-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                <span class="sidebar-text">Peminjaman Alkes</span>
                            </a>
                        </div>

                        <div class="space-y-1 pt-1.5 pb-1">
                            <a href="{{ route('pemeliharaan.index') }}" onclick="closeMobileSidebar()" class="sidebar-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ request()->routeIs('pemeliharaan.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-slate-200 hover:bg-emerald-900 hover:text-white' }}">
                                <svg class="w-5 h-5 text-orange-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.9 6.91a2.12 2.12 0 01-3-3l6.91-6.9a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
                                <div class="flex items-center justify-between w-full sidebar-text">
                                    <span>Perbaikan Alkes</span>
                                    @if ($currentRole === 'elektromedis' && $unreadNotifCount > 0)
                                        <span class="px-2 py-0.5 bg-rose-500 text-white text-xs font-bold rounded-full">
                                            {{ $unreadNotifCount }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        </div>

                        <div class="space-y-1 pt-1.5 pb-1">
                            <a href="{{ route('kalibrasi.index') }}" onclick="closeMobileSidebar()" class="sidebar-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ request()->routeIs('kalibrasi.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-slate-200 hover:bg-emerald-900 hover:text-white' }}">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="sidebar-text">Kalibrasi Alkes</span>
                            </a>
                        </div>

                        <div class="space-y-1 pt-3 pb-1">
                            <div class="px-3 py-1 text-[11px] font-bold text-amber-300 uppercase tracking-wider sidebar-text">Master Data</div>

                            <a href="{{ route('alkes.index') }}" onclick="closeMobileSidebar()" class="sidebar-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ request()->routeIs('alkes.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-slate-200 hover:bg-emerald-900 hover:text-white' }}">
                                <svg class="w-5 h-5 text-teal-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4.8 2.3A.3.3 0 004.5 2h-1a.3.3 0 00-.3.3v5.4A3.8 3.8 0 007 11.4v4.1a3.5 3.5 0 107 0v-4.1a3.8 3.8 0 003.8-3.7V2.3a.3.3 0 00-.3-.3h-1a.3.3 0 00-.3.3v3.7a2 2 0 01-4 0V2.3a.3.3 0 00-.3-.3h-1a.3.3 0 00-.3.3v3.7a2 2 0 01-4 0V2.3z"/></svg>
                                <span class="sidebar-text">Inventaris Alkes</span>
                            </a>
                        </div>

                        <div class="space-y-1 pt-1.5 pb-1">
                            <a href="{{ route('ruangan.index') }}" onclick="closeMobileSidebar()" class="sidebar-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ request()->routeIs('ruangan.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-slate-200 hover:bg-emerald-900 hover:text-white' }}">
                                <svg class="w-5 h-5 text-yellow-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0v-5a2 2 0 012-2h2a2 2 0 012 2v5m-6 0h6"/></svg>
                                <span class="sidebar-text">Daftar Ruangan</span>
                            </a>
                        </div>

                        <div class="space-y-1 pt-1.5 pb-1">
                            <a href="{{ route('activity-logs.index') }}" onclick="closeMobileSidebar()" class="sidebar-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ request()->routeIs('activity-logs.*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'text-slate-200 hover:bg-emerald-900 hover:text-white' }}">
                                <svg class="w-5 h-5 text-cyan-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="sidebar-text">Log Aktivitas</span>
                            </a>
                        </div>
                    </nav>
                </div>

                <div class="p-4 border-t border-emerald-900 text-center sidebar-text">
                    <p class="text-xs text-amber-300/80 font-bold">&copy; 2026 ZAPIN &middot; RSJKO EHD</p>
                </div>
            </div>
        </aside>

        <div id="mainContentWrapper" class="md:ml-64 flex-1 min-h-[calc(100vh-64px)] flex flex-col min-w-0">
            <main class="p-4 sm:p-6 lg:p-8 flex-1 min-w-0 animate-fade-in">
                @if (session('success'))
                    <div id="flashSuccessMsg" class="mb-5 p-4 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-900 font-bold text-sm flex items-center justify-between shadow-sm animate-fade-in">
                        <div class="flex items-center gap-2.5">
                            <i class="ri-checkbox-circle-fill text-emerald-600 text-xl"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button type="button" onclick="document.getElementById('flashSuccessMsg').remove()" class="text-emerald-600 hover:text-emerald-900 transition">
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div id="flashErrorMsg" class="mb-5 p-4 bg-rose-50 border border-rose-300 rounded-xl text-rose-900 font-bold text-sm flex items-center justify-between shadow-sm animate-fade-in">
                        <div class="flex items-center gap-2.5">
                            <i class="ri-error-warning-fill text-rose-600 text-xl"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button type="button" onclick="document.getElementById('flashErrorMsg').remove()" class="text-rose-600 hover:text-rose-900 transition">
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function openMobileSidebar() {
            document.getElementById('sidebarDrawer').classList.remove('-translate-x-full');
            document.getElementById('mobileSidebarOverlay').classList.remove('hidden');
        }

        function closeMobileSidebar() {
            document.getElementById('sidebarDrawer').classList.add('-translate-x-full');
            document.getElementById('mobileSidebarOverlay').classList.add('hidden');
        }

        function toggleNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) dropdown.classList.toggle('dropdown-active');
        }

        document.addEventListener('click', function(event) {
            const bellBtn = document.getElementById('notifBellBtn');
            const dropdown = document.getElementById('notificationDropdown');
            if (bellBtn && dropdown && !bellBtn.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('dropdown-active');
            }
        });

        function toggleSidebarCollapse() {
            const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
            document.documentElement.classList.toggle('sidebar-collapsed', isCollapsed);
            localStorage.setItem('zapin_sidebar_collapsed', isCollapsed ? 'true' : 'false');
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('select').forEach(function(selectEl) {
                if (!selectEl.tomselect && !selectEl.classList.contains('no-tomselect')) {
                    new TomSelect(selectEl, { create: false, maxOptions: 100 });
                }
            });

            var flash = document.getElementById('flashSuccessMsg');
            if (flash) setTimeout(function() { flash.style.transition='opacity 0.3s'; flash.style.opacity='0'; setTimeout(function(){flash.remove()},300); }, 4000);

            var flashErr = document.getElementById('flashErrorMsg');
            if (flashErr) setTimeout(function() { flashErr.style.transition='opacity 0.3s'; flashErr.style.opacity='0'; setTimeout(function(){flashErr.remove()},300); }, 5000);
        });
    </script>
</body>
</html>
