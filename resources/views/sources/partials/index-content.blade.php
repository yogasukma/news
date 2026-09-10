{{-- Sources Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-stone-900">Sources</h1>
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
                <div class="flex items-center gap-2 min-w-0">
                    @if ($feed->favicon_url)
                        <img src="{{ $feed->favicon_url }}"
                             alt=""
                             class="w-4 h-4 rounded-sm shrink-0"
                             loading="lazy"
                             onerror="this.style.display='none'">
                    @endif
                    <span class="font-medium text-stone-700 truncate">{{ $feed->title }}</span>
                    @if ($feed->folder)
                        <span class="text-stone-300">·</span>
                        <span class="text-sm text-stone-500 truncate">{{ $feed->folder->name }}</span>
                    @endif
                </div>
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