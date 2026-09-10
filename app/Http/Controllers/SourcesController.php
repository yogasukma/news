<?php

namespace App\Http\Controllers;

use App\Models\Feed;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SourcesController extends Controller
{
    /**
     * Display every subscribed feed with its last fetch time (TOC style).
     * Lists all feeds regardless of date; never-fetched feeds sink to the bottom.
     */
    public function index(Request $request): Response
    {
        $feeds = Feed::query()
            ->with('folder')
            ->orderByRaw('last_fetched_at IS NULL, last_fetched_at DESC, title ASC')
            ->get();

        $data = ['feeds' => $feeds];

        if ($request->query('fragment') === '1') {
            return response()->view('sources.partials.index-content', $data);
        }

        return response()->view('sources.index', $data);
    }
}
