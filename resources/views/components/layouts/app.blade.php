<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1c1917">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <title>{{ $title ?? 'RSS Reader' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 text-stone-900 antialiased min-h-screen">
    <header class="border-b border-stone-200 bg-white sticky top-0 z-30">
        <div class="max-w-2xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <a href="/" class="text-xl font-semibold tracking-tight text-stone-900 hover:text-stone-600 transition-colors shrink-0">
                RSS Reader
            </a>
            <form action="{{ route('search') }}" method="GET" class="flex-1 max-w-xs">
                <input type="search"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Search articles..."
                       class="w-full text-sm border border-stone-200 rounded-lg px-3 py-1.5 text-stone-600 bg-stone-50 hover:border-stone-300 focus:border-stone-400 focus:bg-white focus:outline-none transition-colors placeholder:text-stone-400">
            </form>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>

    {{-- Article Modal --}}
    <div id="article-modal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/50" id="modal-backdrop"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-start justify-center p-4 py-8">
                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl" id="modal-content">
                    <div class="sticky top-0 bg-white border-b border-stone-200 px-6 py-4 rounded-t-lg flex items-center justify-between">
                        <div>
                            <h2 id="modal-title" class="text-lg font-semibold text-stone-900 pr-8"></h2>
                            <p id="modal-meta" class="text-sm text-stone-500 mt-0.5"></p>
                        </div>
                        <button id="modal-close" class="absolute top-4 right-4 text-stone-400 hover:text-stone-600 transition-colors" aria-label="Close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="modal-body" class="px-6 py-6 prose prose-stone max-w-none"></div>
                    <div class="border-t border-stone-200 px-6 py-3">
                        <a id="modal-original-link" href="#" target="_blank" rel="noopener noreferrer" class="text-sm text-stone-500 hover:text-stone-700 transition-colors inline-flex items-center gap-1">
                            Read original
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (app()->environment('production'))
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js');
        });
    }
    </script>
    @endif
</body>
</html>
