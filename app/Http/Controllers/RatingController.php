<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        // Verify book belongs to author
        $book = Book::findOrFail($request->book_id);
        if ($book->author_id != $request->author_id) {
            return back()->withErrors(['book_id' => 'The selected book does not belong to the chosen author.'])->withInput();
        }

        $userIdentifier = md5($request->ip() . $request->userAgent());

        // 24-hour cooldown
        $recentRating = Rating::where('user_identifier', $userIdentifier)
            ->where('created_at', '>=', now()->subDay())
            ->latest('created_at')
            ->first();

        if ($recentRating) {
            $hoursLeft = max(0, floor(24 - now()->diffInHours($recentRating->created_at)));
            return back()->withErrors([
                'rating' => "Anda harus menunggu sekitar {$hoursLeft} jam sebelum memberi rating lagi."
            ])->withInput();
        }

        // Duplicate rating check
        if (Rating::where('book_id', $request->book_id)
            ->where('user_identifier', $userIdentifier)
            ->exists()) {
            return back()->withErrors(['book_id' => 'You have already rated this book.'])->withInput();
        }

        // Save rating safely
        try {
            Rating::create([
                'book_id' => $request->book_id,
                'user_identifier' => $userIdentifier,
                'rating' => $request->rating,
                'review' => $request->review,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error creating rating: '.$e->getMessage());
            return back()->withErrors([
                'error' => 'An error occurred while saving your rating. Please try again.'
            ])->withInput();
        }

        return redirect()->route('books.index')
            ->with('success', 'Thank you! Your rating has been submitted successfully.');
    }
}
