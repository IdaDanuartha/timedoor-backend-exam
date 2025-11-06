<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::query()
            ->with(['author:id,name', 'categories:id,name'])
            ->leftJoin('ratings', 'books.id', '=', 'ratings.book_id')
            ->groupBy('books.id');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('books.title', 'like', "%{$search}%")
                    ->orWhere('books.isbn', 'like', "%{$search}%")
                    ->orWhere('books.publisher', 'like', "%{$search}%")
                    ->orWhereHas('author', function ($authorQuery) use ($search) {
                        $authorQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by categories (AND / OR)
        if ($request->filled('categories')) {
            $categoryIds = $request->categories;
            $logic = $request->input('category_logic', 'OR');

            if ($logic === 'AND') {
                foreach ($categoryIds as $categoryId) {
                    $query->whereHas('categories', function ($q) use ($categoryId) {
                        $q->where('categories.id', $categoryId);
                    });
                }
            } else {
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            }
        }

        // Filter by author
        if ($request->filled('author_id')) {
            $query->where('books.author_id', $request->author_id);
        }

        // Filter by publication year range
        if ($request->filled('year_from')) {
            $query->where('books.publication_year', '>=', $request->year_from);
        }
        if ($request->filled('year_to')) {
            $query->where('books.publication_year', '<=', $request->year_to);
        }

        // Filter by availability status
        if ($request->filled('availability')) {
            $query->where('books.availability_status', $request->availability);
        }

        // Filter by location
        if ($request->filled('location')) {
            $query->where('books.store_location', $request->location);
        }

        // Filter by rating range
        if ($request->filled('rating_from') || $request->filled('rating_to')) {
            $query->havingRaw('AVG(ratings.rating) >= ?', [$request->input('rating_from', 0)])
                ->havingRaw('AVG(ratings.rating) <= ?', [$request->input('rating_to', 10)]);
        }

        // Sorting
        $sort = $request->input('sort', 'rating');
        switch ($sort) {
            case 'votes':
                $query->orderByRaw('COUNT(ratings.id) DESC');
                break;
            case 'recent':
                $thirtyDaysAgo = now()->subDays(30)->toDateTimeString();

                $query->orderByRaw('
                    (AVG(CASE WHEN ratings.created_at >= ? THEN ratings.rating END) IS NULL) ASC
                ', [$thirtyDaysAgo])
                ->orderByRaw('
                    AVG(CASE WHEN ratings.created_at >= ? THEN ratings.rating END) DESC
                ', [$thirtyDaysAgo]);
                break;
            case 'alphabetical':
                $query->orderBy('books.title', 'ASC');
                break;
            default:
                $query->orderByRaw('AVG(ratings.rating) IS NULL, AVG(ratings.rating) DESC');
        }

        $now = now();
        $sevenDaysAgo = $now->copy()->subDays(7);
        $fourteenDaysAgo = $now->copy()->subDays(14);

        $query->addSelect([
            'books.*',
            DB::raw('AVG(ratings.rating) as avg_rating'),
            DB::raw('COUNT(ratings.id) as total_votes'),
            DB::raw('AVG(CASE WHEN ratings.created_at >= "' . $sevenDaysAgo . '" THEN ratings.rating END) as recent_rating'),
            DB::raw('AVG(CASE WHEN ratings.created_at < "' . $sevenDaysAgo . '" AND ratings.created_at >= "' . $fourteenDaysAgo . '" THEN ratings.rating END) as previous_rating'),
        ]);

        $books = $query->paginate(50);

        // Filters data
        $authors = Author::orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $locations = Book::distinct()->pluck('store_location')->sort()->values();

        $currentYear = date('Y');
        $years = range($currentYear, $currentYear - 100);

        return view('books.index', compact('books', 'authors', 'categories', 'locations', 'years'));
    }
}