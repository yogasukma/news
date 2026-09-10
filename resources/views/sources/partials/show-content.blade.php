{{-- Back to sources --}}
<a href="{{ route('sources') }}"
   data-spa
   class="inline-flex items-center gap-1 text-sm text-stone-600 hover:text-stone-900 transition-colors px-2 py-1 rounded hover:bg-stone-100 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
    </svg>
    Back to sources
</a>

{{-- Source Header --}}
@php
    $siteUrl = $feed->site_url && str_starts_with($feed->site_url, 'http') ? $feed->site_url : null;
@endphp
<div class="mb-6">
    <div class="flex items-center gap-3">
        @if ($feed->favicon_url)
            <img src="{{ $feed->favicon_url }}"
                 alt=""
                 class="w-6 h-6 rounded-sm shrink-0"
                 loading="lazy"
                 onerror="this.style.display='none'">
        @endif
        <h1 class="text-2xl font-bold text-stone-900">{{ $feed->title }}</h1>
    </div>
    @if ($siteUrl)
        <a href="{{ $siteUrl }}"
           target="_blank"
           rel="noopener noreferrer"
           title="{{ $siteUrl }}"
           class="inline-block mt-1 text-sm text-stone-500 hover:text-stone-900 hover:underline transition-colors break-all">
            {{ $siteUrl }}
        </a>
    @endif
    <p class="text-sm text-stone-500 mt-2">
        {{ $articles->total() }} {{ Str('article')->plural($articles->total()) }}
    </p>
</div>

{{-- Article List --}}
@if ($articles->isEmpty())
    <div class="text-center py-16">
        <p class="text-stone-400 text-lg">No articles yet.</p>
    </div>
@else
    <div class="space-y-4">
        @foreach ($articles as $article)
            <x-partials.article-card :article="$article" :mode="'recent'" />
        @endforeach
    </div>

    {{-- Pagination --}}
    @if ($articles->hasPages())
        <div class="mt-8 flex items-center justify-center gap-2">
            @if ($articles->onFirstPage())
                <span class="text-sm text-stone-400 px-3 py-1.5">Previous</span>
            @else
                <a href="{{ $articles->previousPageUrl() }}" class="text-sm text-stone-600 hover:text-stone-900 px-3 py-1.5 rounded hover:bg-stone-100 transition-colors" data-spa>Previous</a>
            @endif

            <span class="text-sm text-stone-500">
                Page {{ $articles->currentPage() }} of {{ $articles->lastPage() }}
            </span>

            @if ($articles->hasMorePages())
                <a href="{{ $articles->nextPageUrl() }}" class="text-sm text-stone-600 hover:text-stone-900 px-3 py-1.5 rounded hover:bg-stone-100 transition-colors" data-spa>Next</a>
            @else
                <span class="text-sm text-stone-400 px-3 py-1.5">Next</span>
            @endif
        </div>
    @endif
@endif