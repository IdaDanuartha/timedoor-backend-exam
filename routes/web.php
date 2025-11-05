<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\RatingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'index'])->name('books.index');
Route::get('/books', [BookController::class, 'index'])->name('books.list');

Route::get('/authors/top', [AuthorController::class, 'index'])->name('authors.top');

Route::get('/ratings/create', [RatingController::class, 'create'])->name('ratings.create');
Route::post('/ratings', [RatingController::class, 'store'])->name('ratings.store');
Route::get('/books/by-author', [RatingController::class, 'getBooksByAuthor'])->name('books.by-author');