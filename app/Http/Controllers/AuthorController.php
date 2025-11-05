<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'popularity');
        
        $baseQuery = Author::query()
            ->select('authors.*')
            ->join('books', 'authors.id', '=', 'books.author_id')
            ->join('ratings', 'books.id', '=', 'ratings.book_id')
            ->groupBy('authors.id');

        switch ($tab) {
            case 'rating':
                // By Average Rating
                $authors = $baseQuery
                    ->selectRaw('
                        authors.*,
                        AVG(ratings.rating) as avg_rating,
                        COUNT(DISTINCT ratings.id) as total_ratings,
                        MAX(CASE WHEN book_ratings.avg_rating IS NOT NULL THEN book_ratings.book_title END) as best_book,
                        MAX(CASE WHEN book_ratings.avg_rating IS NOT NULL THEN book_ratings.avg_rating END) as best_book_rating,
                        MIN(CASE WHEN book_ratings.avg_rating IS NOT NULL THEN book_ratings.book_title END) as worst_book,
                        MIN(CASE WHEN book_ratings.avg_rating IS NOT NULL THEN book_ratings.avg_rating END) as worst_book_rating
                    ')
                    ->leftJoin(DB::raw('(
                        SELECT 
                            books.author_id,
                            books.title as book_title,
                            AVG(ratings.rating) as avg_rating
                        FROM books
                        JOIN ratings ON books.id = ratings.book_id
                        GROUP BY books.id, books.author_id
                    ) as book_ratings'), 'authors.id', '=', 'book_ratings.author_id')
                    ->orderByRaw('AVG(ratings.rating) DESC')
                    ->limit(20)
                    ->get();
                break;

            case 'trending':
                // Trending Authors (last 30 days vs previous 30 days)
                $authors = $baseQuery
                    ->selectRaw('
                        authors.*,
                        AVG(CASE WHEN ratings.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN ratings.rating ELSE NULL END) as recent_avg,
                        AVG(CASE WHEN ratings.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND ratings.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) THEN ratings.rating ELSE NULL END) as previous_avg,
                        COUNT(CASE WHEN ratings.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE NULL END) as recent_count,
                        COUNT(DISTINCT ratings.id) as total_ratings,
                        (
                            AVG(CASE WHEN ratings.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN ratings.rating ELSE NULL END) -
                            AVG(CASE WHEN ratings.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND ratings.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) THEN ratings.rating ELSE NULL END)
                        ) * LOG(1 + COUNT(CASE WHEN ratings.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE NULL END)) as trending_score
                    ')
                    ->havingRaw('recent_avg IS NOT NULL AND previous_avg IS NOT NULL')
                    ->orderByRaw('trending_score DESC')
                    ->limit(20)
                    ->get();
                break;

            default: // popularity
                // By Popularity (voters count with rating > 5)
                $authors = $baseQuery
                    ->selectRaw('
                        authors.*,
                        COUNT(DISTINCT CASE WHEN ratings.rating > 5 THEN ratings.id END) as popularity_votes,
                        COUNT(DISTINCT ratings.id) as total_ratings,
                        AVG(ratings.rating) as avg_rating
                    ')
                    ->havingRaw('popularity_votes > 0')
                    ->orderByRaw('popularity_votes DESC')
                    ->limit(20)
                    ->get();
        }

        // Get best and worst rated books for each author
        foreach ($authors as $author) {
            if ($tab !== 'rating') {
                $bookStats = DB::table('books')
                    ->select('books.title', DB::raw('AVG(ratings.rating) as avg_rating'))
                    ->join('ratings', 'books.id', '=', 'ratings.book_id')
                    ->where('books.author_id', $author->id)
                    ->groupBy('books.id', 'books.title')
                    ->orderBy('avg_rating', 'DESC')
                    ->get();

                if ($bookStats->isNotEmpty()) {
                    $author->best_book = $bookStats->first()->title;
                    $author->best_book_rating = round($bookStats->first()->avg_rating, 2);
                    $author->worst_book = $bookStats->last()->title;
                    $author->worst_book_rating = round($bookStats->last()->avg_rating, 2);
                }
            }
        }

        return view('authors.index', compact('authors', 'tab'));
    }
}
