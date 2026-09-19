@if ($paginator->total() > 0)
    <div class="flex flex-col lg:flex-row items-center justify-between gap-4 text-xs font-medium py-1">

        <div class="flex items-center gap-3 flex-wrap">
            <div class="text-slate-800 font-bold">
                Menampilkan
                <span class="font-extrabold text-slate-900">{{ $paginator->firstItem() ?? 1 }}</span>
                sampai
                <span class="font-extrabold text-slate-900">{{ $paginator->lastItem() ?? $paginator->total() }}</span>
                dari
                <span class="font-extrabold text-emerald-700">{{ number_format($paginator->total()) }}</span>
                data
            </div>

            <div class="flex items-center gap-1 bg-slate-200/80 p-0.5 rounded-xl font-extrabold border border-slate-300">
                @php
                    $currentPerPage = request('per_page', '50');
                @endphp

                <a href="{{ request()->fullUrlWithQuery(['per_page' => '50', 'page' => 1]) }}"
                   class="px-2.5 py-1 rounded-lg transition text-[11px] {{ $currentPerPage == '50' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-700 hover:text-slate-900' }}"
                   title="Menampilkan 50 data per halaman">
                    50 / Hal
                </a>

                <a href="{{ request()->fullUrlWithQuery(['per_page' => '100', 'page' => 1]) }}"
                   class="px-2.5 py-1 rounded-lg transition text-[11px] {{ $currentPerPage == '100' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-700 hover:text-slate-900' }}"
                   title="Menampilkan 100 data per halaman">
                    100 / Hal
                </a>

                <a href="{{ request()->fullUrlWithQuery(['per_page' => 'all', 'page' => 1]) }}"
                   class="px-2.5 py-1 rounded-lg transition text-[11px] flex items-center gap-1 {{ $currentPerPage == 'all' ? 'bg-amber-500 text-white shadow-xs' : 'text-amber-800 hover:text-amber-950 hover:bg-amber-100' }}"
                   title="Tampilkan Seluruh Data tanpa Pembagian Halaman">
                    <i class="ri-eye-line"></i> Semua Data
                </a>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            @if ($paginator->lastPage() > 1 && $currentPerPage !== 'all')
                <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-1.5 font-bold text-slate-700 bg-white px-2.5 py-1 rounded-xl border border-slate-300 shadow-2xs">
                    @foreach (request()->except(['page', '_token']) as $key => $val)
                        @if (is_array($val))
                            @foreach ($val as $subVal)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                        @endif
                    @endforeach

                    <span class="text-slate-600 text-xs font-semibold">Hal.</span>
                    <input type="number"
                           name="page"
                           min="1"
                           max="{{ $paginator->lastPage() }}"
                           value="{{ $paginator->currentPage() }}"
                           class="w-12 h-7 px-1 bg-slate-50 border border-slate-300 rounded-lg text-center font-extrabold text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                           title="Ketik nomor halaman lalu tekan Enter atau klik Tuju">
                    <span class="text-slate-400 text-xs font-semibold">/ {{ $paginator->lastPage() }}</span>
                    <button type="submit" class="h-7 px-2.5 bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white rounded-lg font-bold text-xs transition flex items-center gap-1 shadow-2xs" title="Tuju Halaman">
                        <span>Tuju</span>
                        <i class="ri-arrow-right-line text-[11px]"></i>
                    </button>
                </form>
            @endif

            @if ($paginator->hasPages() && $currentPerPage !== 'all')
                <div class="flex items-center gap-1 overflow-x-auto max-w-full">
                    @if ($paginator->onFirstPage())
                        <span class="w-8 h-8 flex items-center justify-center text-slate-400 bg-slate-200/70 rounded-lg text-xs font-bold cursor-not-allowed">
                            <i class="ri-arrow-left-s-line text-sm"></i>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center text-slate-800 bg-white hover:bg-emerald-50 hover:text-emerald-800 border border-slate-300 rounded-lg text-xs font-bold transition shadow-2xs" title="Halaman Sebelumnya">
                            <i class="ri-arrow-left-s-line text-sm"></i>
                        </a>
                    @endif

                    @php
                        $currentPage = $paginator->currentPage();
                        $lastPage = $paginator->lastPage();
                        $pagesToDisplay = [];
                        for ($i = 1; $i <= min(3, $lastPage); $i++) { $pagesToDisplay[] = $i; }
                        for ($i = max(1, $currentPage - 1); $i <= min($lastPage, $currentPage + 1); $i++) { $pagesToDisplay[] = $i; }
                        for ($i = max(1, $lastPage - 2); $i <= $lastPage; $i++) { $pagesToDisplay[] = $i; }
                        $pagesToDisplay = array_unique($pagesToDisplay);
                        sort($pagesToDisplay);
                    @endphp

                    @php $prevNum = 0; @endphp
                    @foreach ($pagesToDisplay as $page)
                        @if ($prevNum > 0 && $page - $prevNum > 1)
                            <span class="px-1.5 py-1 text-slate-400 font-bold text-xs select-none">&middot;&middot;&middot;</span>
                        @endif

                        @if ($page == $currentPage)
                            <span class="min-w-[2rem] h-8 px-2 flex items-center justify-center bg-emerald-700 text-white font-black text-xs rounded-lg shadow-xs">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $paginator->url($page) }}" class="min-w-[2rem] h-8 px-2 flex items-center justify-center bg-white hover:bg-emerald-50 text-slate-800 hover:text-emerald-800 border border-slate-300 rounded-lg text-xs font-bold transition shadow-2xs">
                                {{ $page }}
                            </a>
                        @endif

                        @php $prevNum = $page; @endphp
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center text-slate-800 bg-white hover:bg-emerald-50 hover:text-emerald-800 border border-slate-300 rounded-lg text-xs font-bold transition shadow-2xs" title="Halaman Berikutnya">
                            <i class="ri-arrow-right-s-line text-sm"></i>
                        </a>
                    @else
                        <span class="w-8 h-8 flex items-center justify-center text-slate-400 bg-slate-200/70 rounded-lg text-xs font-bold cursor-not-allowed">
                            <i class="ri-arrow-right-s-line text-sm"></i>
                        </span>
                    @endif
                </div>
            @endif
        </div>

    </div>
@endif
