<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function create()
    {
        $authors = Author::orderBy('name')->get(['id', 'name']);
        return view('ratings.create', compact('authors'));
    }

    public function getBooksByAuthor(Request $request)
    {
        $authorId = $request->input('author_id');
        
        if (!$authorId) {
            return response()->json([]);
        }

        $books = Book::where('author_id', $authorId)
            ->orderBy('title')
            ->get(['id', 'title']);

        return response()->json($books);
    }

    public function store(Request $request)
    {
        $request->validate([
            'author_id' => 'required|exists:authors,id',
            'book_id' => 'required|exists:books,id',
            'rating' => 'required|integer|min:1|max:10',
            'review' => 'nullable|string|max:1000',
        ]);

        // Verify book belongs to selected author
        $book = Book::findOrFail($request->book_id);
        if ($book->author_id != $request->author_id) {
            return back()->withErrors(['book_id' => 'The selected book does not belong to the chosen author.'])->withInput();
        }

        // Get user identifier (IP address + User Agent hash)
        $userIdentifier = md5($request->ip() . $request->userAgent());

        // Check if user has rated any book in the last 24 hours
        $lastRating = Rating::where('user_identifier', $userIdentifier)
            ->where('created_at', '>=', now()->subHours(24))
            ->first();

        if ($lastRating) {
            $secondsPassed = abs(now()->diffInSeconds($lastRating->created_at, false)); 

            // Kalau last rating dibuat di masa lalu, maka sisanya = 86400 - selisih
            $secondsRemaining = max(0, 86400 - $secondsPassed);

            $hours = floor($secondsRemaining / 3600);
            $minutes = floor(($secondsRemaining % 3600) / 60);
            $seconds = $secondsRemaining % 60;

            $formatted = sprintf('%d jam %d menit %d detik', $hours, $minutes, $seconds);

            return back()->withErrors([
                'rating' => "Kamu harus menunggu {$formatted} lagi sebelum bisa memberikan rating baru."
            ])->withInput();
        }

        // Check for duplicate rating on same book
        $existingRating = Rating::where('user_identifier', $userIdentifier)
            ->where('book_id', $request->book_id)
            ->first();

        if ($existingRating) {
            return back()->withErrors([
                'book_id' => 'You have already rated this book.'
            ])->withInput();
        }

        // Create rating
        try {
            Rating::create([
                'book_id' => $request->book_id,
                'user_identifier' => $userIdentifier,
                'rating' => $request->rating,
                'review' => $request->review,
            ]);

            return redirect()->route('books.index')
                ->with('success', 'Thank you! Your rating has been submitted successfully.');
        } catch (\Exception $e) {
            logger()->error('Error creating rating: ' . $e->getMessage());
            return back()->withErrors([
                'error' => 'An error occurred while submitting your rating. Please try again.'
            ])->withInput();
        }
    }
}
