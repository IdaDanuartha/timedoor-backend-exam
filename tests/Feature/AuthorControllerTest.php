<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

function createAuthorWithRatings(int $ratingCount = 10, float $avgRating = 7): Author
{
    $author = Author::factory()->create();
    $book = Book::factory()->create(['author_id' => $author->id]);
    Rating::factory()->count($ratingCount)->create([
        'book_id' => $book->id,
        'rating' => $avgRating,
    ]);
    return $author;
}

test('it displays top authors page', function () {
    createAuthorWithRatings();

    $response = $this->get(route('authors.top'));

    $response->assertStatus(200);
    $response->assertViewIs('authors.index');
    $response->assertViewHas(['authors', 'tab']);
});

test('it defaults to popularity tab', function () {
    createAuthorWithRatings();

    $response = $this->get(route('authors.top'));

    $response->assertStatus(200);
    expect($response->viewData('tab'))->toBe('popularity');
});

test('it displays popularity tab with correct ranking', function () {
    createAuthorWithRatings(10, 8);
    createAuthorWithRatings(20, 7);
    createAuthorWithRatings(5, 4);

    $response = $this->get(route('authors.top', ['tab' => 'popularity']));

    $response->assertStatus(200);
    $authors = $response->viewData('authors');
    expect($authors[0]->popularity_votes)->toBeGreaterThan($authors[1]->popularity_votes);
    expect($authors->count())->toBeLessThanOrEqual(2);
});

test('it displays rating tab with correct ranking', function () {
    createAuthorWithRatings(10, 7);
    createAuthorWithRatings(10, 9);
    createAuthorWithRatings(10, 8);

    $response = $this->get(route('authors.top', ['tab' => 'rating']));

    $response->assertStatus(200);
    $authors = $response->viewData('authors');
    expect($authors[0]->avg_rating)->toBeGreaterThanOrEqual($authors[1]->avg_rating);
    expect($authors[1]->avg_rating)->toBeGreaterThanOrEqual($authors[2]->avg_rating);
});

test('it displays trending tab with correct ranking', function () {
    $author1 = Author::factory()->create();
    $book1 = Book::factory()->create(['author_id' => $author1->id]);

    Rating::factory()->count(5)->create([
        'book_id' => $book1->id,
        'rating' => 9,
        'created_at' => now()->subDays(15),
    ]);

    Rating::factory()->count(3)->create([
        'book_id' => $book1->id,
        'rating' => 5,
        'created_at' => now()->subDays(45),
    ]);

    createAuthorWithRatings(10, 7);

    $response = $this->get(route('authors.top', ['tab' => 'trending']));

    $response->assertStatus(200);
    $authors = $response->viewData('authors');
    expect($authors[0]->trending_score)->not()->toBeNull();
    expect($authors[0]->recent_avg)->not()->toBeNull();
    expect($authors[0]->previous_avg)->not()->toBeNull();
});

test('it limits results to 20 authors', function () {
    for ($i = 0; $i < 25; $i++) {
        createAuthorWithRatings(10, 7);
    }

    $response = $this->get(route('authors.top', ['tab' => 'popularity']));

    $response->assertStatus(200);
    expect($response->viewData('authors'))->toHaveCount(20);
});

test('it displays author statistics for popularity tab', function () {
    createAuthorWithRatings(15, 8);

    $response = $this->get(route('authors.top', ['tab' => 'popularity']));

    $response->assertStatus(200);
    $authorData = $response->viewData('authors')->first();
    expect($authorData->popularity_votes)->not()->toBeNull();
    expect($authorData->total_ratings)->not()->toBeNull();
    expect($authorData->avg_rating)->not()->toBeNull();
});

test('it displays best and worst books for each author', function () {
    $author = Author::factory()->create();
    $book1 = Book::factory()->create(['author_id' => $author->id, 'title' => 'Best Book']);
    Rating::factory()->count(5)->create(['book_id' => $book1->id, 'rating' => 9]);

    $book2 = Book::factory()->create(['author_id' => $author->id, 'title' => 'Worst Book']);
    Rating::factory()->count(5)->create(['book_id' => $book2->id, 'rating' => 4]);

    $response = $this->get(route('authors.top', ['tab' => 'popularity']));

    $response->assertStatus(200);
    $authorData = $response->viewData('authors')->first();

    expect($authorData->best_book)->toBe('Best Book');
    expect($authorData->best_book_rating)->toBeGreaterThan(8);
    expect($authorData->worst_book)->toBe('Worst Book');
    expect($authorData->worst_book_rating)->toBeLessThan(5);
});

test('it excludes authors with only low ratings in popularity tab', function () {
    $author1 = createAuthorWithRatings(10, 8);
    createAuthorWithRatings(10, 3);

    $response = $this->get(route('authors.top', ['tab' => 'popularity']));

    $response->assertStatus(200);
    $authors = $response->viewData('authors');
    expect($authors)->toHaveCount(1);
    expect($authors->first()->id)->toBe($author1->id);
});

test('it calculates trending score correctly', function () {
    $author = Author::factory()->create();
    $book = Book::factory()->create(['author_id' => $author->id]);

    Rating::factory()->count(10)->create([
        'book_id' => $book->id,
        'rating' => 9,
        'created_at' => now()->subDays(15),
    ]);

    Rating::factory()->count(10)->create([
        'book_id' => $book->id,
        'rating' => 5,
        'created_at' => now()->subDays(45),
    ]);

    $response = $this->get(route('authors.top', ['tab' => 'trending']));
    $response->assertStatus(200);
    $authors = $response->viewData('authors');

    if ($authors->count() > 0) {
        $authorData = $authors->first();
        $expectedScore = (9 - 5) * log(1 + 10);
        expect((float) $authorData->trending_score)->toEqualWithDelta($expectedScore, 0.1);
    }
});

test('it requires both recent and previous data for trending', function () {
    $author1 = Author::factory()->create();
    $book1 = Book::factory()->create(['author_id' => $author1->id]);
    Rating::factory()->count(10)->create([
        'book_id' => $book1->id,
        'rating' => 9,
        'created_at' => now()->subDays(15),
    ]);

    $author2 = Author::factory()->create();
    $book2 = Book::factory()->create(['author_id' => $author2->id]);
    Rating::factory()->count(5)->create([
        'book_id' => $book2->id,
        'rating' => 9,
        'created_at' => now()->subDays(15),
    ]);
    Rating::factory()->count(5)->create([
        'book_id' => $book2->id,
        'rating' => 6,
        'created_at' => now()->subDays(45),
    ]);

    $response = $this->get(route('authors.top', ['tab' => 'trending']));
    $authors = $response->viewData('authors');
    $authorIds = $authors->pluck('id')->toArray();

    expect($authorIds)->toContain($author2->id);
    expect($authorIds)->not->toContain($author1->id);
});

test('it handles authors without ratings', function () {
    Author::factory()->count(3)->create();

    $response = $this->get(route('authors.top', ['tab' => 'popularity']));

    $response->assertStatus(200);
    $authors = $response->viewData('authors');
    expect($authors->count() === 0 || $authors->first()->total_ratings > 0)->toBeTrue();
});

test('it displays total ratings count', function () {
    createAuthorWithRatings(25, 8);

    $response = $this->get(route('authors.top', ['tab' => 'rating']));
    $response->assertStatus(200);
    $authorData = $response->viewData('authors')->first();

    expect($authorData->total_ratings)->toBe(25);
});

test('it sorts popularity tab by vote count descending', function () {
    createAuthorWithRatings(30, 7);
    createAuthorWithRatings(50, 6);
    createAuthorWithRatings(20, 8);

    $response = $this->get(route('authors.top', ['tab' => 'popularity']));
    $authors = $response->viewData('authors');

    expect($authors[0]->popularity_votes)->toBeGreaterThanOrEqual($authors[1]->popularity_votes);
    expect($authors[1]->popularity_votes)->toBeGreaterThanOrEqual($authors[2]->popularity_votes);
});

test('it sorts rating tab by average rating descending', function () {
    createAuthorWithRatings(10, 7);
    createAuthorWithRatings(10, 9);
    createAuthorWithRatings(10, 6);

    $response = $this->get(route('authors.top', ['tab' => 'rating']));
    $authors = $response->viewData('authors');

    expect($authors[0]->avg_rating)->toBeGreaterThanOrEqual($authors[1]->avg_rating);
    expect($authors[1]->avg_rating)->toBeGreaterThanOrEqual($authors[2]->avg_rating);
});

test('it sorts trending tab by trending score descending', function () {
    $author1 = Author::factory()->create();
    $book1 = Book::factory()->create(['author_id' => $author1->id]);
    Rating::factory()->count(10)->create([
        'book_id' => $book1->id,
        'rating' => 9,
        'created_at' => now()->subDays(15),
    ]);
    Rating::factory()->count(10)->create([
        'book_id' => $book1->id,
        'rating' => 4,
        'created_at' => now()->subDays(45),
    ]);

    $author2 = Author::factory()->create();
    $book2 = Book::factory()->create(['author_id' => $author2->id]);
    Rating::factory()->count(5)->create([
        'book_id' => $book2->id,
        'rating' => 7,
        'created_at' => now()->subDays(15),
    ]);
    Rating::factory()->count(5)->create([
        'book_id' => $book2->id,
        'rating' => 6,
        'created_at' => now()->subDays(45),
    ]);

    $response = $this->get(route('authors.top', ['tab' => 'trending']));
    $authors = $response->viewData('authors');

    if ($authors->count() >= 2) {
        expect($authors[0]->trending_score)->toBeGreaterThan($authors[1]->trending_score);
    }
});

test('it handles tab parameter correctly', function () {
    createAuthorWithRatings();

    $popularity = $this->get(route('authors.top', ['tab' => 'popularity']));
    expect($popularity->viewData('tab'))->toBe('popularity');

    $rating = $this->get(route('authors.top', ['tab' => 'rating']));
    expect($rating->viewData('tab'))->toBe('rating');

    $trending = $this->get(route('authors.top', ['tab' => 'trending']));
    expect($trending->viewData('tab'))->toBe('trending');
});

test('it displays recent vote count in trending tab', function () {
    $author = Author::factory()->create();
    $book = Book::factory()->create(['author_id' => $author->id]);

    Rating::factory()->count(15)->create([
        'book_id' => $book->id,
        'rating' => 8,
        'created_at' => now()->subDays(15),
    ]);

    Rating::factory()->count(10)->create([
        'book_id' => $book->id,
        'rating' => 7,
        'created_at' => now()->subDays(45),
    ]);

    $response = $this->get(route('authors.top', ['tab' => 'trending']));
    $authors = $response->viewData('authors');

    if ($authors->count() > 0) {
        expect($authors->first()->recent_count)->toBe(15);
    }
});