<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem - ZAPIN RSJKO Engku Haji Daud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <style>
        body, input, button, select, textarea { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        .role-option { transition: all 0.15s ease; }
        .role-option:hover { transform: translateY(-1px); }
        .role-option.selected { border-color: #facc15 !important; background-color: #065f46 !important; }

        .ts-control {
            background-color: #064e3b !important;
            border: 1.5px solid #facc15 !important;
            border-radius: 0.625rem !important;
            padding: 0.6rem 0.875rem !important;
            font-size: 0.875rem !important;
            font-weight: 700 !important;
            color: #ffffff !important;
        }
        .ts-wrapper.dropdown-active .ts-control {
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }
        .ts-dropdown {
            background-color: #064e3b !important;
            border: 1.5px solid #facc15 !important;
            border-radius: 0.625rem !important;
            color: #ffffff !important;
            box-shadow: 0 20px 40px -8px rgba(0, 0, 0, 0.8) !important;
            z-index: 999999 !important;
            max-height: 200px !important;
            overflow-y: auto !important;
        }
        .ts-dropdown .option {
            padding: 0.65rem 0.875rem !important;
            color: #ffffff !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
        }
        .ts-dropdown .option:hover, .ts-dropdown .option.active {
            background-color: #059669 !important;
            color: #facc15 !important;
        }
        .ts-control input { color: #ffffff !important; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .login-card { animation: fadeIn 0.4s ease-out; }
    </style>
</head>
<body class="h-screen w-screen overflow-hidden flex items-center justify-center p-4 relative bg-slate-950">

    <div class="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat scale-105 filter blur-xs" style="background-image: url('{{ asset('images/RSJKO EHD.jpg') }}');"></div>
    <div class="fixed inset-0 z-0 bg-gradient-to-br from-emerald-950/65 via-slate-950/55 to-emerald-950/70 backdrop-blur-xs"></div>

    <div class="login-card relative z-10 w-full max-w-md bg-slate-900/75 backdrop-blur-md border-2 border-emerald-400/60 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-6">

        <div class="text-center space-y-3">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-500 to-amber-400 text-white shadow-xl shadow-emerald-600/50 mx-auto flex items-center justify-center border-2 border-amber-300">
                <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35zM10.5 11h3v3h2v-3h3V9h-3V6h-2v3h-3v2z"/></svg>
            </div>
            <div>
                <h2 class="text-3xl font-black text-white tracking-wider">ZAPIN</h2>
                <p class="text-xs font-black text-amber-300 tracking-wider uppercase mt-1">RSJKO Engku Haji Daud</p>
                <p class="text-xs text-slate-100 font-semibold mt-1">Zona Aplikasi Pengelolaan Inventaris Alat Kesehatan</p>
            </div>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div class="space-y-2.5">
                <label class="block text-xs font-black text-amber-300 uppercase tracking-wider">PILIH PERAN AKSES</label>

                <div class="space-y-2.5">
                    <label class="role-option selected flex items-center gap-3 p-3.5 bg-emerald-900/80 backdrop-blur-xs border-2 border-amber-400 rounded-xl cursor-pointer shadow-sm">
                        <input type="radio" name="role" value="elektromedis" checked onchange="handleRoleChange(this)" class="w-4 h-4 text-amber-400 focus:ring-amber-400 border-slate-600 bg-transparent">
                        <div>
                            <p class="font-extrabold text-white text-sm">Instalasi Elektromedis</p>
                            <p class="text-xs text-slate-100 font-semibold">Otoritas perbaikan & pengembalian unit alkes</p>
                        </div>
                    </label>

                    <label class="role-option flex items-center gap-3 p-3.5 bg-slate-900/60 backdrop-blur-xs border border-slate-700/80 rounded-xl cursor-pointer shadow-sm">
                        <input type="radio" name="role" value="ruangan" onchange="handleRoleChange(this)" class="w-4 h-4 text-amber-400 focus:ring-amber-400 border-slate-600 bg-transparent">
                        <div>
                            <p class="font-extrabold text-white text-sm">Instalasi / Ruangan</p>
                            <p class="text-xs text-slate-200 font-semibold">Pelaporan kerusakan & mutasi alkes</p>
                        </div>
                    </label>

                    <div id="ruanganDropdownContainer" class="overflow-hidden transition-all duration-250 ease-in-out" style="max-height:0;opacity:0">
                        <div class="pt-1 pb-1 space-y-1.5">
                            <label class="block text-xs font-black text-amber-300 uppercase tracking-wider">PILIH RUANGAN</label>
                            <select id="ruanganSelect" name="ruangan_id" class="w-full">
                                @foreach ($ruanganList as $ruang)
                                    @if ($ruang->nama_ruangan !== 'Elektromedis')
                                        <option value="{{ $ruang->id }}">{{ $ruang->nama_ruangan }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <label class="role-option flex items-center gap-3 p-3.5 bg-slate-900/60 backdrop-blur-xs border border-slate-700/80 rounded-xl cursor-pointer shadow-sm">
                        <input type="radio" name="role" value="tata_usaha" onchange="handleRoleChange(this)" class="w-4 h-4 text-amber-400 focus:ring-amber-400 border-slate-600 bg-transparent">
                        <div>
                            <p class="font-extrabold text-white text-sm">Manajemen / Penunjang</p>
                            <p class="text-xs text-slate-200 font-semibold">Pengawasan inventaris</p>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-emerald-600/90 to-emerald-700/90 hover:from-emerald-500 hover:to-emerald-600 text-white font-black text-base rounded-xl shadow-xl shadow-emerald-950/60 border border-amber-300/50 backdrop-blur-xs transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h6a3 3 0 013 3v1"/></svg>
                Masuk ke ZAPIN
            </button>
        </form>

        <div class="text-center pt-2 border-t border-slate-800/80">
            <p class="text-xs text-amber-300/90 font-bold">&copy; 2026 ZAPIN &middot; RSJKO Engku Haji Daud</p>
        </div>
    </div>

    <script>
        let tsInstance = null;

        document.addEventListener('DOMContentLoaded', function() {
            const sel = document.getElementById('ruanganSelect');
            if (sel) {
                tsInstance = new TomSelect('#ruanganSelect', {
                    create: false,
                    placeholder: 'Ketik nama ruangan...',
                    maxOptions: 50,
                    dropdownParent: 'body'
                });
            }
        });

        function handleRoleChange(radio) {
            document.querySelectorAll('.role-option').forEach(function(el) {
                el.classList.remove('selected');
                el.style.borderColor = '#334155';
                el.style.backgroundColor = 'rgba(15, 23, 42, 0.6)';
            });
            const parent = radio.closest('.role-option');
            parent.classList.add('selected');
            parent.style.borderColor = '#facc15';
            parent.style.backgroundColor = 'rgba(6, 95, 70, 0.85)';

            const container = document.getElementById('ruanganDropdownContainer');
            if (radio.value === 'ruangan') {
                container.style.maxHeight = '90px';
                container.style.opacity = '1';
                setTimeout(function() {
                    if (document.querySelector('input[name="role"]:checked')?.value === 'ruangan') {
                        container.style.overflow = 'visible';
                    }
                }, 250);
            } else {
                container.style.overflow = 'hidden';
                container.style.maxHeight = '0px';
                container.style.opacity = '0';
            }
        }
    </script>
</body>
</html>
