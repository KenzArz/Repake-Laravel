@extends('layouts.app')

@section('title', 'Dashboard Admin - RePake')
@section('hide-footer', '1')

@section('content')
    <div class="mx-auto max-w-6xl px-4 pb-28 pt-8 sm:px-6 md:pb-12">
        {{-- Header --}}
        <div class="max-w-2xl">
            <h1 class="font-heading text-[28px] font-extrabold leading-tight tracking-tight text-neutral-900 sm:text-3xl">
                Dashboard
            </h1>
            <p class="mt-1.5 text-sm leading-relaxed text-neutral-600">
                Tinjau laporan produk, rapikan kategori, dan atur banner beranda RePake.
            </p>
        </div>

        {{-- Tabs (mengikuti gaya tab di halaman profile) --}}
        <div class="mt-6 border-b border-neutral-200">
            <div class="flex items-center gap-6 overflow-x-auto" role="tablist" aria-label="Menu dashboard admin">
                @foreach ([
                    'laporan' => ['label' => 'Laporan', 'count' => $reports->total()],
                    'kategori' => ['label' => 'Kategori', 'count' => $categories->count()],
                    'banner' => ['label' => 'Banner', 'count' => $banners->count()],
                ] as $key => $item)
                    <a href="{{ route('admin.dashboard', ['tab' => $key]) }}" data-admin-tab="{{ $key }}"
                        id="admin-tab-{{ $key }}" role="tab"
                        aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                        aria-controls="admin-panel-{{ $key }}"
                        class="shrink-0 border-b-2 pb-2.5 text-xs font-bold transition {{ $tab === $key ? 'border-neutral-900 text-neutral-900' : 'border-transparent text-neutral-500 hover:text-neutral-800' }}">
                        {{ $item['label'] }} ({{ $item['count'] }})
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Panel: Laporan --}}
        <section id="admin-panel-laporan" data-admin-panel="laporan" role="tabpanel"
            aria-labelledby="admin-tab-laporan" class="pt-5 {{ $tab === 'laporan' ? '' : 'hidden' }}">
            @include('admin.partials.reports-tab')
        </section>

        {{-- Panel: Kategori --}}
        <section id="admin-panel-kategori" data-admin-panel="kategori" role="tabpanel"
            aria-labelledby="admin-tab-kategori" class="pt-5 {{ $tab === 'kategori' ? '' : 'hidden' }}">
            @include('admin.partials.categories-tab')
        </section>

        {{-- Panel: Banner --}}
        <section id="admin-panel-banner" data-admin-panel="banner" role="tabpanel"
            aria-labelledby="admin-tab-banner" class="pt-5 {{ $tab === 'banner' ? '' : 'hidden' }}">
            @include('admin.partials.banners-tab')
        </section>
    </div>

    {{-- Modal detail laporan --}}
    <div id="report-modal" class="fixed inset-0 z-[60] hidden p-4 sm:p-6" role="dialog" aria-modal="true"
        aria-labelledby="report-modal-title">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-report-backdrop></div>
        <div
            class="relative mx-auto mt-4 flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between border-b border-neutral-200 px-5 py-4">
                <h2 id="report-modal-title" class="font-heading text-sm font-bold text-neutral-900">Detail laporan</h2>
                <button type="button" data-report-close
                    class="grid size-8 place-items-center rounded-full text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                    aria-label="Tutup detail laporan">
                    <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current stroke-2" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="report-modal-body" class="overflow-y-auto p-5 sm:p-6">
                <p class="py-10 text-center text-xs text-neutral-500">Memuat detail laporan…</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /* ===== Tabs ===== */
            const tabs = document.querySelectorAll('[data-admin-tab]');
            const panels = document.querySelectorAll('[data-admin-panel]');
            const activeTabClasses = ['border-neutral-900', 'text-neutral-900'];
            const idleTabClasses = ['border-transparent', 'text-neutral-500', 'hover:text-neutral-800'];

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function (event) {
                    event.preventDefault();

                    const key = tab.dataset.adminTab;

                    tabs.forEach(function (item) {
                        const isActive = item === tab;

                        activeTabClasses.forEach(function (className) {
                            item.classList.toggle(className, isActive);
                        });

                        idleTabClasses.forEach(function (className) {
                            item.classList.toggle(className, !isActive);
                        });

                        item.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });

                    panels.forEach(function (panel) {
                        panel.classList.toggle('hidden', panel.dataset.adminPanel !== key);
                    });

                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', key);
                    url.searchParams.delete('report');
                    history.replaceState({}, '', url);
                });
            });

            /* ===== Modal detail laporan ===== */
            const modal = document.getElementById('report-modal');
            const body = document.getElementById('report-modal-body');

            if (!modal || !body) return;

            let lastFocused = null;

            const showLoading = function () {
                body.innerHTML = '<p class="py-10 text-center text-xs text-neutral-500">Memuat detail laporan…</p>';
            };

            const openReport = async function (url) {
                lastFocused = document.activeElement;
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                showLoading();
                modal.querySelector('[data-report-close]')?.focus();

                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Gagal memuat laporan.');
                    }

                    body.innerHTML = await response.text();
                } catch (error) {
                    body.innerHTML = '<p class="py-10 text-center text-xs font-medium text-danger-shade">' +
                        'Detail laporan tidak bisa dimuat. Tutup lalu coba lagi.</p>';
                }
            };

            const closeReport = function () {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');

                if (lastFocused instanceof HTMLElement) {
                    lastFocused.focus();
                }
            };

            document.querySelectorAll('[data-report-row]').forEach(function (row) {
                row.addEventListener('click', function (event) {
                    if (event.target.closest('a, button, form')) return;
                    openReport(row.dataset.reportUrl);
                });

                row.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openReport(row.dataset.reportUrl);
                    }
                });
            });

            document.querySelectorAll('[data-report-close], [data-report-backdrop]').forEach(function (element) {
                element.addEventListener('click', closeReport);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeReport();
                }
            });

            @if ($openReportId)
                openReport(@json(route('admin.reports.show', $openReportId)));
            @endif
        });
    </script>
@endpush
