<?php

use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ArticleController::class, 'index']);
Route::get('/date/{date}', [ArticleController::class, 'index'])->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')->name('date');
Route::get('/article/{article}', [ArticleController::class, 'show'])->name('article.show');
