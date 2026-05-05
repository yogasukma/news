{{-- Date Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-stone-900">
        @if ($mode === 'recent')
            {{ 'Recent Feeds' }}
        @elseif ($mode === 'today')
            {{ "Today's Feeds" }}
        @else
            {{ $currentDate->format('F j, Y') }}
        @endif
    </h1>
    <p class="text-sm text-stone-500 mt-1">
        {{ $articles->count() }} {{ Str('article')->plural($articles->count()) }}
    </p>
</div>

{{-- Controls: Date Navigation + Folder Filter --}}
<div class="space-y-3 mb-8">
    {{-- Date Navigation (hidden in recent mode — articles span multiple dates) --}}
    @if ($mode !== 'recent')
        <div class="flex items-center gap-3">
            <a href="{{ $previousDate->format('Y-m-d') === now()->format('Y-m-d') ? '/' : route('date', $previousDate->format('Y-m-d')) }}"
               class="inline-flex items-center gap-1 text-sm text-stone-600 hover:text-stone-900 transition-colors px-2 py-1 rounded hover:bg-stone-100"
               data-spa>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                {{ $previousDate->isYesterday() ? 'Yesterday' : $previousDate->format('M j') }}
            </a>

            <span class="text-stone-300">|</span>

            @if ($nextDate)
                <a href="{{ $nextDate->isToday() ? '/' : route('date', $nextDate->format('Y-m-d')) }}"
                   class="inline-flex items-center gap-1 text-sm text-stone-600 hover:text-stone-900 transition-colors px-2 py-1 rounded hover:bg-stone-100"
                   data-spa>
                    {{ $nextDate->isToday() ? 'Today' : $nextDate->format('M j') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            @else
                <span class="text-sm text-stone-400 px-2 py-1">Next day</span>
            @endif

            <span class="text-stone-300">|</span>

            <label class="text-sm text-stone-500 inline-flex items-center gap-1">
                <input type="date"
                       value="{{ $currentDate->format('Y-m-d') }}"
                       max="{{ now()->format('Y-m-d') }}"
                       class="text-sm border border-stone-200 rounded px-2 py-1 text-stone-600 bg-white hover:border-stone-300 transition-colors"
                       data-spa-date>
            </label>
        </div>
    @endif

    {{-- Folder Filter --}}
    @if ($folders->isNotEmpty())
        <div class="flex flex-wrap gap-1.5">
            <a href="{{ request()->url() }}"
               class="text-xs px-3 py-1.5 rounded-full transition-colors {{ ! $activeFolder ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}"
               data-spa>
                All
            </a>
            @foreach ($folders as $folder)
                <a href="{{ request()->url() }}?folder={{ $folder->slug }}"
                   class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $activeFolder?->id === $folder->id ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}"
                   data-spa>
                    {{ $folder->name }}
                </a>
            @endforeach
        </div>
    @endif
</div>

{{-- Article List --}}
@if ($articles->isEmpty())
    <div class="text-center py-16">
        <p class="text-stone-400 text-lg">{{ $mode === 'recent' ? 'No articles found.' : 'No articles on this date.' }}</p>
    </div>
@else
    <div class="space-y-4">
        @foreach ($articles as $article)
            <x-partials.article-card :article="$article" :mode="$mode" />
        @endforeach
    </div>
@endif
