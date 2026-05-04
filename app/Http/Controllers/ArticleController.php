<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Folder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;

class ArticleController extends Controller
{
    /**
     * Display articles for a given date (defaults to today).
     */
    public function index(Request $request, ?string $date = null): Response
    {
        $date = $this->resolveDate($date);
        $folderSlug = $request->query('folder');
        $folder = $folderSlug ? Folder::where('slug', $folderSlug)->first() : null;

        $articles = Article::query()
            ->with('feed.folder')
            ->whereDate('published_at', $date->toDateString())
            ->when($folder, fn ($q) => $q->whereHas('feed', fn ($q) => $q->where('folder_id', $folder->id)))
            ->orderByDesc('published_at')
            ->get();

        $folders = Folder::orderBy('name')->get();

        return response()->view('articles.index', [
            'articles' => $articles,
            'currentDate' => $date,
            'previousDate' => $date->copy()->subDay(),
            'nextDate' => $date->isToday() ? null : $date->copy()->addDay(),
            'folders' => $folders,
            'activeFolder' => $folder,
        ]);
    }

    /**
     * Return a single article as JSON for the modal.
     */
    public function show(Article $article): JsonResponse
    {
        $article->load('feed');

        return response()->json([
            'id' => $article->id,
            'title' => $article->title,
            'url' => $article->url,
            'content' => $article->content,
            'author' => $article->author,
            'published_at' => $article->published_at->toIso8601String(),
            'cover_image' => $article->cover_image,
            'feed' => [
                'id' => $article->feed->id,
                'title' => $article->feed->title,
                'site_url' => $article->feed->site_url,
            ],
        ]);
    }

    /**
     * Search articles by keyword with optional date and folder filters.
     */
    public function search(Request $request): Response
    {
        $query = trim($request->query('q', ''));
        $date = $request->query('date');
        $folderSlug = $request->query('folder');
        $folder = $folderSlug ? Folder::where('slug', $folderSlug)->first() : null;

        $articles = Article::query()
            ->with('feed.folder')
            ->when($query !== '', function ($q) use ($query) {
                $term = '%'.mb_strtolower($query).'%';
                $q->where(function ($q) use ($term) {
                    $q->whereRaw('LOWER(title) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(content) LIKE ?', [$term]);
                });
            })
            ->when($date, function ($q) use ($date) {
                try {
                    $parsed = Carbon::createFromFormat('Y-m-d', $date);
                    $q->whereDate('published_at', $parsed->toDateString());
                } catch (\Exception) {
                    // Invalid date — skip filter
                }
            })
            ->when($folder, fn ($q) => $q->whereHas('feed', fn ($q) => $q->where('folder_id', $folder->id)))
            ->orderByDesc('published_at')
            ->paginate(30)
            ->withQueryString();

        $folders = Folder::orderBy('name')->get();

        return response()->view('articles.search', [
            'articles' => $articles,
            'query' => $query,
            'folders' => $folders,
            'activeFolder' => $folder,
        ]);
    }

    private function resolveDate(?string $date): Carbon
    {
        if ($date === null) {
            return Date::now();
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date);

            if ($parsed->isFuture()) {
                return Date::now();
            }

            return $parsed;
        } catch (\Exception) {
            return Date::now();
        }
    }
}
