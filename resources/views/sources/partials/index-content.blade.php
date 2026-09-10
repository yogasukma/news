{{-- Back to feeds --}}
<a href="/"
   data-spa
   class="inline-flex items-center gap-1 text-sm text-stone-600 hover:text-stone-900 transition-colors px-2 py-1 rounded hover:bg-stone-100 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
    </svg>
    Back to feeds
</a>

{{-- Sources Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-stone-900">RSS Sources</h1>
    <p class="text-sm text-stone-500 mt-1">
        {{ $feeds->count() }} {{ Str('source')->plural($feeds->count()) }}
    </p>
</div>

{{-- Feed List (TOC style) --}}
@if ($feeds->isEmpty())
    <div class="text-center py-16">
        <p class="text-stone-400 text-lg">No sources yet.</p>
    </div>
@else
    <div class="bg-white rounded-lg border border-stone-200 divide-y divide-stone-100">
        @foreach ($feeds as $feed)
            <div class="flex items-center justify-between gap-4 px-4 py-3">
                <a href="{{ route('sources.show', $feed) }}"
                   data-spa
                   class="flex items-center gap-2 min-w-0 group">
                    @if ($feed->favicon_url)
                        <img src="{{ $feed->favicon_url }}"
                             alt=""
                             class="w-4 h-4 rounded-sm shrink-0"
                             loading="lazy"
                             onerror="this.style.display='none'">
                    @endif
                    <span class="font-medium text-stone-700 truncate group-hover:text-stone-900">{{ $feed->title }}</span>
                    @if ($feed->folder)
                        <span class="text-stone-300">·</span>
                        <span class="text-sm text-stone-500 truncate">{{ $feed->folder->name }}</span>
                    @endif
                </a>
                <div class="shrink-0">
                    @if ($feed->last_fetched_at)
                        <time datetime="{{ $feed->last_fetched_at->toIso8601String() }}"
                              title="{{ $feed->last_fetched_at->format('M j, Y g:i A') }}"
                              class="text-sm text-stone-500">
                            {{ $feed->last_fetched_at->diffForHumans() }}
                        </time>
                    @else
                        <span class="text-sm text-stone-400">Never</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif