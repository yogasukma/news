<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Response;

class RssFeedController extends Controller
{
    public function __invoke(): Response
    {
        $articles = Article::query()
            ->with('feed')
            ->orderByDesc('published_at')
            ->paginate(100);

        $lastBuildDate = $articles->first()?->published_at ?? now();

        return response()->view('rss.feed', [
            'articles' => $articles,
            'lastBuildDate' => $lastBuildDate,
        ])->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
