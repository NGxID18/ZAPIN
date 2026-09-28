<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZAPIN</title>
    <!-- Tailwind CSS (Offline-first local dengan CDN fallback) -->
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}" onerror="this.onerror=null;this.src='https://cdn.tailwindcss.com';"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="{{ asset('vendor/remixicon/remixicon.css') }}" rel="stylesheet" onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css';">

    <style>
        body, input, button, select, textarea { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        .role-option { transition: all 0.15s ease; }
        .role-option:hover { transform: translateY(-1px); }
        .role-option.selected { border-color: #facc15 !important; background-color: #065f46 !important; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .login-card { animation: fadeIn 0.4s ease-out; }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }
        .animate-shake { animation: shake 0.3s ease-in-out; }

        /* Custom Scrollbar for room options */
        #ruanganOptionsList::-webkit-scrollbar {
            width: 5px;
        }
        #ruanganOptionsList::-webkit-scrollbar-track {
            background: rgba(6, 78, 59, 0.5);
            border-radius: 4px;
        }
        #ruanganOptionsList::-webkit-scrollbar-thumb {
            background: #059669;
            border-radius: 4px;
        }
        #ruanganOptionsList::-webkit-scrollbar-thumb:hover {
            background: #10b981;
        }
    </style>
</head>
<body class="h-screen w-screen overflow-hidden flex items-center justify-center p-4 relative bg-slate-950">

    <div class="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat scale-105 filter blur-xs" style="background-image: url('{{ asset('images/RSJKO EHD.jpg') }}');"></div>
    <div class="fixed inset-0 z-0 bg-gradient-to-br from-emerald-950/65 via-slate-950/55 to-emerald-950/70 backdrop-blur-xs"></div>

    <div class="login-card relative z-10 w-full max-w-md bg-slate-900/75 backdrop-blur-md border-2 border-emerald-400/60 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-6">

        <!-- Header Title (Icon dihapus sesuai instruksi) -->
        <div class="text-center space-y-2">
            <div>
                <h2 class="text-3xl font-black text-white tracking-wider">ZAPIN</h2>
                <p class="text-xs font-black text-amber-300 tracking-wider uppercase mt-1">RSJKO Engku Haji Daud</p>
                <p class="text-xs text-slate-100 font-semibold mt-1">Zona Aplikasi Pengelolaan Inventaris Alat Kesehatan</p>
            </div>
        </div>

        @if (session('error') || $errors->any())
            <div class="p-3 bg-rose-500/20 border border-rose-500/50 rounded-xl text-rose-200 text-xs font-bold flex items-center gap-2 animate-shake">
                <i class="ri-error-warning-line text-rose-400 text-base shrink-0"></i>
                <span>{{ session('error') ?? $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4" id="loginForm">
            @csrf

            <div class="space-y-2.5">
                <label class="block text-xs font-black text-amber-300 uppercase tracking-wider">MASUK SEBAGAI</label>

                <div class="space-y-2.5">
                    <!-- Peran 1: Elektromedis -->
                    <label class="role-option selected flex items-center gap-3.5 p-3.5 bg-emerald-900/80 backdrop-blur-xs border-2 border-amber-400 rounded-xl cursor-pointer shadow-sm">
                        <input type="radio" name="role" value="elektromedis" checked onchange="handleRoleChange(this)" class="w-4 h-4 text-amber-400 focus:ring-amber-400 border-slate-600 bg-transparent shrink-0">
                        <span class="font-black text-white text-base">Instalasi Elektromedis</span>
                    </label>

                    <!-- Peran 2: Ruangan -->
                    <label class="role-option flex items-center gap-3.5 p-3.5 bg-slate-900/60 backdrop-blur-xs border border-slate-700/80 rounded-xl cursor-pointer shadow-sm">
                        <input type="radio" name="role" value="ruangan" onchange="handleRoleChange(this)" class="w-4 h-4 text-amber-400 focus:ring-amber-400 border-slate-600 bg-transparent shrink-0">
                        <span class="font-black text-white text-base">Instalasi / Ruangan</span>
                    </label>

                    <!-- Kontainer Dropdown Ruangan -->
                    <div id="ruanganDropdownContainer" class="overflow-hidden transition-all duration-250 ease-in-out" style="max-height:0;opacity:0">
                        <div class="pt-1 pb-1 space-y-1.5 relative">
                            <label class="block text-xs font-black text-amber-300 uppercase tracking-wider">PILIH RUANGAN</label>

                            <!-- Hidden input untuk disubmit ke form -->
                            <input type="hidden" name="ruangan_id" id="ruanganIdInput" value="">

                            <!-- Trigger Box: Desain Original #064e3b dengan Placeholder Transparan & Logo Panah Keatas/Kebawah -->
                            <button type="button"
                                    id="ruanganTriggerBtn"
                                    onclick="toggleRuanganDropdown(event)"
                                    class="w-full flex items-center justify-between text-left shadow-sm transition-all duration-150 focus:outline-none"
                                    style="background-color: #064e3b; border: 1.5px solid #059669; border-radius: 0.625rem; padding: 0.6rem 0.875rem;">
                                
                                <span id="selectedRuanganText" class="text-white/40 font-semibold text-sm truncate select-none transition-colors duration-150">
                                    Pilih Ruangan / Instalasi...
                                </span>

                                <!-- Logo Keatas & Kebawah (Dual Chevron Arrow) -->
                                <div id="dropdownArrow" class="flex items-center text-emerald-300/80 transition-transform duration-200 shrink-0 ml-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                    </svg>
                                </div>
                            </button>

                            <!-- Dropdown Menu List: Desain Original #064e3b dengan Animasi Buka/Tutup/Pilih Halus -->
                            <div id="ruanganDropdownMenu"
                                 class="absolute left-0 right-0 top-full mt-1.5 shadow-2xl z-50 overflow-hidden transition-all duration-200 ease-out origin-top pointer-events-none opacity-0 -translate-y-2 scale-98"
                                 style="background-color: #064e3b; border: 1.5px solid #059669; border-radius: 0.625rem; max-height: 210px;">
                                
                                <div id="ruanganOptionsList" class="max-h-[200px] overflow-y-auto divide-y divide-emerald-800/40">
                                    @foreach ($ruanganList as $ruang)
                                        @if ($ruang->nama_ruangan !== 'Elektromedis')
                                            <div onclick="selectRuangan({{ $ruang->id }}, '{{ addslashes($ruang->nama_ruangan) }}')"
                                                 class="ruangan-option cursor-pointer transition-colors duration-100 flex items-center justify-between"
                                                 style="padding: 0.65rem 0.875rem; color: #ffffff; font-size: 0.875rem; font-weight: 600;"
                                                 onmouseover="this.style.backgroundColor='#059669'; this.style.color='#facc15';"
                                                 onmouseout="if(document.getElementById('ruanganIdInput').value != '{{ $ruang->id }}'){ this.style.backgroundColor='transparent'; this.style.color='#ffffff'; }"
                                                 data-id="{{ $ruang->id }}">
                                                <span>{{ $ruang->nama_ruangan }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <p id="ruanganErrorText" class="hidden text-rose-300 text-xs font-bold pt-0.5 flex items-center gap-1">
                                <i class="ri-error-warning-line"></i> Silakan pilih ruangan terlebih dahulu.
                            </p>
                        </div>
                    </div>

                    <!-- Peran 3: Tata Usaha -->
                    <label class="role-option flex items-center gap-3.5 p-3.5 bg-slate-900/60 backdrop-blur-xs border border-slate-700/80 rounded-xl cursor-pointer shadow-sm">
                        <input type="radio" name="role" value="tata_usaha" onchange="handleRoleChange(this)" class="w-4 h-4 text-amber-400 focus:ring-amber-400 border-slate-600 bg-transparent shrink-0">
                        <span class="font-black text-white text-base">Manajemen / Penunjang</span>
                    </label>
                </div>
            </div>

            <!-- Input Kata Sandi -->
            <div class="space-y-1.5 pt-1">
                <label for="passwordInput" class="block text-xs font-black text-amber-300 uppercase tracking-wider">KATA SANDI</label>
                <div class="relative">
                    <input type="password"
                           name="password"
                           id="passwordInput"
                           required
                           placeholder="Masukkan kata sandi..."
                           class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700/80 rounded-xl text-white font-bold text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 backdrop-blur-xs transition pr-11">
                    <button type="button"
                            onclick="togglePasswordVisibility()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-amber-300 transition focus:outline-none"
                            title="Tampilkan / Sembunyikan Sandi">
                        <i id="passwordToggleIcon" class="ri-eye-line text-lg"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-emerald-600/90 to-emerald-700/90 hover:from-emerald-500 hover:to-emerald-600 text-white font-black text-base rounded-xl shadow-xl shadow-emerald-950/60 border border-amber-300/50 backdrop-blur-xs transition flex items-center justify-center">
                Masuk
            </button>
        </form>

        <div class="text-center pt-2 border-t border-slate-800/80">
            <p class="text-xs text-amber-300/90 font-bold">&copy; 2026 ZAPIN &middot; RSJKO Engku Haji Daud</p>
        </div>
    </div>

    <script>
        let isDropdownOpen = false;

        function toggleRuanganDropdown(e) {
            if (e) e.stopPropagation();
            if (isDropdownOpen) {
                closeRuanganDropdown();
            } else {
                openRuanganDropdown();
            }
        }

        function openRuanganDropdown() {
            const menu = document.getElementById('ruanganDropdownMenu');
            const arrow = document.getElementById('dropdownArrow');
            const btn = document.getElementById('ruanganTriggerBtn');

            isDropdownOpen = true;

            // Transisi animasi buka (slide down + fade in)
            menu.classList.remove('pointer-events-none', 'opacity-0', '-translate-y-2', 'scale-98');
            menu.classList.add('opacity-100', 'translate-y-0', 'scale-100');
            arrow.classList.add('rotate-180', 'text-amber-300');
            btn.style.borderColor = '#facc15';
        }

        function closeRuanganDropdown() {
            const menu = document.getElementById('ruanganDropdownMenu');
            const arrow = document.getElementById('dropdownArrow');
            const btn = document.getElementById('ruanganTriggerBtn');

            isDropdownOpen = false;

            // Transisi animasi tutup (slide up + fade out)
            menu.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
            menu.classList.add('pointer-events-none', 'opacity-0', '-translate-y-2', 'scale-98');
            arrow.classList.remove('rotate-180', 'text-amber-300');
            btn.style.borderColor = '#059669';
        }

        function selectRuangan(id, name) {
            const idInput = document.getElementById('ruanganIdInput');
            const textSpan = document.getElementById('selectedRuanganText');
            const errorText = document.getElementById('ruanganErrorText');
            const btn = document.getElementById('ruanganTriggerBtn');

            idInput.value = id;

            // Animasi transisi teks ruangan: dari transparan ke bold white
            textSpan.textContent = name;
            textSpan.className = "text-white font-bold text-sm truncate select-none transition-all duration-150";

            // Update status visual opsi terpilih
            document.querySelectorAll('.ruangan-option').forEach(opt => {
                if (opt.dataset.id == id) {
                    opt.style.backgroundColor = '#059669';
                    opt.style.color = '#facc15';
                } else {
                    opt.style.backgroundColor = 'transparent';
                    opt.style.color = '#ffffff';
                }
            });

            if (errorText) errorText.classList.add('hidden');
            if (btn) btn.classList.remove('animate-shake');

            // Animasi tutup dropdown
            closeRuanganDropdown();
        }

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
                closeRuanganDropdown();
                container.style.overflow = 'hidden';
                container.style.maxHeight = '0px';
                container.style.opacity = '0';
            }
        }

        // Event listener klik di luar untuk menutup dropdown
        document.addEventListener('click', function(e) {
            const container = document.getElementById('ruanganDropdownContainer');
            if (isDropdownOpen && container && !container.contains(e.target)) {
                closeRuanganDropdown();
            }
        });

        // Event listener tombol Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isDropdownOpen) {
                closeRuanganDropdown();
            }
        });

        // Validasi Submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const activeRole = document.querySelector('input[name="role"]:checked')?.value;
            if (activeRole === 'ruangan') {
                const idInput = document.getElementById('ruanganIdInput');
                if (!idInput || !idInput.value) {
                    e.preventDefault();
                    const errorText = document.getElementById('ruanganErrorText');
                    const btn = document.getElementById('ruanganTriggerBtn');
                    if (errorText) errorText.classList.remove('hidden');
                    if (btn) {
                        btn.style.borderColor = '#f87171';
                        btn.classList.add('animate-shake');
                        setTimeout(() => btn.classList.remove('animate-shake'), 400);
                    }
                    openRuanganDropdown();
                    return false;
                }
            }
        });

        function togglePasswordVisibility() {
            const pwd = document.getElementById('passwordInput');
            const icon = document.getElementById('passwordToggleIcon');
            if (!pwd) return;
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'ri-eye-off-line text-lg text-amber-300';
            } else {
                pwd.type = 'password';
                icon.className = 'ri-eye-line text-lg';
            }
        }
    </script>
</body>
</html>
