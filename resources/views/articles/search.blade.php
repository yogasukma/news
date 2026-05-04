<x-layouts.app>
    {{-- Search Header --}}
    <div class="mb-6">
        @if ($query)
            <h1 class="text-2xl font-bold text-stone-900">
                Search results for "{{ $query }}"
            </h1>
            <p class="text-sm text-stone-500 mt-1">
                {{ $articles->total() }} {{ Str('result')->plural($articles->total()) }}
            </p>
        @else
            <h1 class="text-2xl font-bold text-stone-900">Search</h1>
            <p class="text-sm text-stone-500 mt-1">Enter a search term to find articles.</p>
        @endif
    </div>

    {{-- Folder Filter --}}
    @if ($folders->isNotEmpty())
        <div class="flex flex-wrap gap-1.5 mb-8">
            <a href="{{ route('search', array_filter(['q' => $query])) }}"
               class="text-xs px-3 py-1.5 rounded-full transition-colors {{ ! $activeFolder ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                All
            </a>
            @foreach ($folders as $folder)
                <a href="{{ route('search', array_filter(['q' => $query, 'folder' => $folder->slug])) }}"
                   class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $activeFolder?->id === $folder->id ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                    {{ $folder->name }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- Results --}}
    @if ($query && $articles->isEmpty())
        <div class="text-center py-16">
            <p class="text-stone-400 text-lg">No results found for "{{ $query }}".</p>
        </div>
    @elseif ($articles->isNotEmpty())
        <div class="space-y-4">
            @foreach ($articles as $article)
                <x-partials.article-card :article="$article" />
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($articles->hasPages())
            <div class="mt-8 flex items-center justify-center gap-2">
                @if ($articles->onFirstPage())
                    <span class="text-sm text-stone-400 px-3 py-1.5">Previous</span>
                @else
                    <a href="{{ $articles->previousPageUrl() }}" class="text-sm text-stone-600 hover:text-stone-900 px-3 py-1.5 rounded hover:bg-stone-100 transition-colors">Previous</a>
                @endif

                <span class="text-sm text-stone-500">
                    Page {{ $articles->currentPage() }} of {{ $articles->lastPage() }}
                </span>

                @if ($articles->hasMorePages())
                    <a href="{{ $articles->nextPageUrl() }}" class="text-sm text-stone-600 hover:text-stone-900 px-3 py-1.5 rounded hover:bg-stone-100 transition-colors">Next</a>
                @else
                    <span class="text-sm text-stone-400 px-3 py-1.5">Next</span>
                @endif
            </div>
        @endif
    @endif
</x-layouts.app>
