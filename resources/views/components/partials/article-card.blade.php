@props(['article'])

<article class="bg-white rounded-lg border border-stone-200 overflow-hidden hover:border-stone-300 transition-colors group cursor-pointer"
         onclick="openArticle({{ $article->id }})"
         role="button"
         tabindex="0"
         aria-label="Read: {{ $article->title }}"
         onkeydown="if(event.key==='Enter') openArticle({{ $article->id }})">

    @if ($article->cover_image)
        <div class="aspect-[2/1] overflow-hidden bg-stone-100">
            <img src="{{ $article->cover_image }}"
                 alt=""
                 class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-200"
                 loading="lazy">
        </div>
    @endif

    <div class="p-4">
        <h2 class="text-base font-semibold text-stone-900 leading-snug group-hover:text-stone-700 transition-colors">
            {{ $article->title }}
        </h2>

        <div class="flex items-center gap-2 mt-2 text-sm text-stone-500">
            <span class="font-medium text-stone-600">{{ $article->feed->title }}</span>
            @if ($article->feed->folder)
                <span class="text-stone-300">·</span>
                <span>{{ $article->feed->folder->name }}</span>
            @endif
            <span class="text-stone-300">·</span>
            <time datetime="{{ $article->published_at->toIso8601String() }}">
                {{ $article->published_at->format('g:i A') }}
            </time>
        </div>

        @if ($article->content)
            <p class="mt-2 text-sm text-stone-600 line-clamp-2">
                {{ Str::limit(strip_tags($article->content), 200) }}
            </p>
        @endif

        @if ($article->author)
            <p class="mt-2 text-xs text-stone-400">
                by {{ $article->author }}
            </p>
        @endif
    </div>
</article>
