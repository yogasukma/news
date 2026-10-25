{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
<title>{{ config('app.name', 'Feeds') }}</title>
<link>{{ url('/') }}</link>
<description>Latest articles from {{ config('app.name', 'Feeds') }}</description>
<lastBuildDate>{{ $lastBuildDate->toRfc2822String() }}</lastBuildDate>
@foreach ($articles as $article)
<item>
<title>{{ $article->title }}</title>
<link>{{ $article->url }}</link>
<guid>{{ $article->url }}</guid>
<pubDate>{{ $article->published_at?->toRfc2822String() ?? $article->created_at->toRfc2822String() }}</pubDate>
<category>{{ $article->feed->title ?? 'Unknown source' }}</category>
@if ($article->author)
<author>{{ $article->author }}</author>
@endif
<description><![CDATA[{!! str_replace(']]>', ']]]]><![CDATA[>', $article->content ?? '') !!}]]></description>
<content:encoded><![CDATA[{!! str_replace(']]>', ']]]]><![CDATA[>', $article->content ?? '') !!}]]></content:encoded>
</item>
@endforeach
</channel>
</rss>
