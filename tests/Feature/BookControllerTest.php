<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('it displays books list page', function () {
    Book::factory()->count(3)->create();

    $response = $this->get(route('books.index'));

    $response->assertStatus(200)
        ->assertViewIs('books.index')
        ->assertViewHas(['books', 'authors', 'categories', 'locations', 'years']);
});

test('it paginates books with 50 per page', function () {
    Book::factory()->count(60)->create();

    $response = $this->get(route('books.index'));

    $response->assertStatus(200);
    expect($response->viewData('books')->perPage())->toBe(50);
    expect($response->viewData('books')->total())->toBe(60);
});

test('it searches books by title', function () {
    Book::factory()->create(['title' => 'The Great Gatsby']);
    Book::factory()->create(['title' => 'To Kill a Mockingbird']);
    Book::factory()->create(['title' => 'Great Expectations']);

    $response = $this->get(route('books.index', ['search' => 'Great']));

    $response->assertStatus(200)
        ->assertSee('The Great Gatsby')
        ->assertSee('Great Expectations')
        ->assertDontSee('To Kill a Mockingbird');
});

test('it searches books by author name', function () {
    $author = Author::factory()->create(['name' => 'Stephen King']);
    $book1 = Book::factory()->create(['author_id' => $author->id]);

    $response = $this->get(route('books.index', ['search' => 'Stephen King']));

    $response->assertStatus(200)
        ->assertSee($book1->title);
});

test('it searches books by isbn', function () {
    $book = Book::factory()->create(['isbn' => '978-3-16-148410-0']);
    Book::factory()->count(2)->create();

    $response = $this->get(route('books.index', ['search' => '978-3-16-148410-0']));

    $response->assertStatus(200)->assertSee($book->title);
});

test('it searches books by publisher', function () {
    $book1 = Book::factory()->create(['publisher' => 'Penguin Books']);
    $book2 = Book::factory()->create(['publisher' => 'HarperCollins']);

    $response = $this->get(route('books.index', ['search' => 'Penguin']));

    $response->assertStatus(200)
        ->assertSee($book1->title)
        ->assertDontSee($book2->title);
});

test('it filters books by single category', function () {
    $category = Category::factory()->create(['name' => 'Fiction']);
    $book1 = Book::factory()->create();
    $book1->categories()->attach($category->id);
    $book2 = Book::factory()->create();

    $response = $this->get(route('books.index', ['categories' => [$category->id]]));

    $response->assertStatus(200)
        ->assertSee($book1->title)
        ->assertDontSee($book2->title);
});

test('it filters books by multiple categories with OR logic', function () {
    $category1 = Category::factory()->create(['name' => 'Fiction']);
    $category2 = Category::factory()->create(['name' => 'Mystery']);
    $book1 = Book::factory()->create();
    $book1->categories()->attach($category1->id);
    $book2 = Book::factory()->create();
    $book2->categories()->attach($category2->id);
    $book3 = Book::factory()->create();

    $response = $this->get(route('books.index', [
        'categories' => [$category1->id, $category2->id],
        'category_logic' => 'OR'
    ]));

    $response->assertStatus(200)
        ->assertSee($book1->title)
        ->assertSee($book2->title)
        ->assertDontSee($book3->title);
});

test('it filters books by multiple categories with AND logic', function () {
    $category1 = Category::factory()->create(['name' => 'Fiction']);
    $category2 = Category::factory()->create(['name' => 'Mystery']);
    $book1 = Book::factory()->create();
    $book1->categories()->attach([$category1->id, $category2->id]);
    $book2 = Book::factory()->create();
    $book2->categories()->attach($category1->id);

    $response = $this->get(route('books.index', [
        'categories' => [$category1->id, $category2->id],
        'category_logic' => 'AND'
    ]));

    $response->assertStatus(200)
        ->assertSee($book1->title)
        ->assertDontSee($book2->title);
});

test('it filters books by author', function () {
    $author = Author::factory()->create();
    $book1 = Book::factory()->create(['author_id' => $author->id]);

    $response = $this->get(route('books.index', ['author_id' => $author->id]));

    $response->assertStatus(200)
        ->assertSee($book1->title);
});

test('it filters books by publication year range', function () {
    $book1 = Book::factory()->create(['publication_year' => 2020]);
    $book2 = Book::factory()->create(['publication_year' => 2021]);
    $book3 = Book::factory()->create(['publication_year' => 2022]);
    $book4 = Book::factory()->create(['publication_year' => 2023]);

    $response = $this->get(route('books.index', [
        'year_from' => 2021,
        'year_to' => 2022
    ]));

    $response->assertStatus(200)
        ->assertDontSee($book1->title)
        ->assertSee($book2->title)
        ->assertSee($book3->title)
        ->assertDontSee($book4->title);
});

test('it filters books by availability status', function () {
    $book1 = Book::factory()->available()->create();
    $book2 = Book::factory()->rented()->create();
    $book3 = Book::factory()->reserved()->create();

    $response = $this->get(route('books.index', ['availability' => 'available']));

    $response->assertStatus(200)
        ->assertSee($book1->title)
        ->assertDontSee($book2->title)
        ->assertDontSee($book3->title);
});

test('it filters books by store location', function () {
    $book1 = Book::factory()->create(['store_location' => 'Section A']);
    $book2 = Book::factory()->create(['store_location' => 'Section B']);

    $response = $this->get(route('books.index', ['location' => 'Section A']));

    $response->assertStatus(200)
        ->assertSee($book1->title)
        ->assertDontSee($book2->title);
});

test('it filters books by rating range', function () {
    $book1 = Book::factory()->create();
    Rating::factory()->count(5)->create(['book_id' => $book1->id, 'rating' => 9]);

    $response = $this->get(route('books.index', [
        'rating_from' => 7,
        'rating_to' => 10
    ]));

    $response->assertStatus(200);
});

test('it sorts books by average rating default', function () {
    $book1 = Book::factory()->create(['title' => 'Book A']);
    Rating::factory()->create(['book_id' => $book1->id, 'rating' => 5]);
    $book2 = Book::factory()->create(['title' => 'Book B']);
    Rating::factory()->create(['book_id' => $book2->id, 'rating' => 9]);
    $book3 = Book::factory()->create(['title' => 'Book C']);
    Rating::factory()->create(['book_id' => $book3->id, 'rating' => 7]);

    $response = $this->get(route('books.index'));

    $response->assertStatus(200);
    expect($response->viewData('books')->first()->title)->toBe('Book B');
});

test('it sorts books by total votes', function () {
    $book1 = Book::factory()->create(['title' => 'Book A']);
    Rating::factory()->count(10)->create(['book_id' => $book1->id]);
    $book2 = Book::factory()->create(['title' => 'Book B']);
    Rating::factory()->count(20)->create(['book_id' => $book2->id]);

    $response = $this->get(route('books.index', ['sort' => 'votes']));

    $response->assertStatus(200);
    expect($response->viewData('books')->first()->title)->toBe('Book B');
});

test('it sorts books by recent popularity', function () {
    $book1 = Book::factory()->create(['title' => 'Book A']);
    Rating::factory()->create([
        'book_id' => $book1->id,
        'rating' => 5,
        'created_at' => now()->subDays(20)
    ]);

    $book2 = Book::factory()->create(['title' => 'Book B']);
    Rating::factory()->create([
        'book_id' => $book2->id,
        'rating' => 9,
        'created_at' => now()->subDays(10)
    ]);

    $response = $this->get(route('books.index', ['sort' => 'recent']));

    $response->assertStatus(200);
    expect($response->viewData('books')->first()->title)->toBe('Book B');
});

test('it sorts books alphabetically', function () {
    Book::factory()->create(['title' => 'Zebra Book']);
    Book::factory()->create(['title' => 'Apple Book']);
    Book::factory()->create(['title' => 'Mango Book']);

    $response = $this->get(route('books.index', ['sort' => 'alphabetical']));

    $response->assertStatus(200);
    expect($response->viewData('books')->first()->title)->toBe('Apple Book');
});

test('it displays rating statistics for books', function () {
    $book = Book::factory()->create();
    Rating::factory()->create(['book_id' => $book->id, 'rating' => 8]);
    Rating::factory()->create(['book_id' => $book->id, 'rating' => 9]);
    Rating::factory()->create(['book_id' => $book->id, 'rating' => 7]);

    $response = $this->get(route('books.index'));

    $response->assertStatus(200);
    $bookData = $response->viewData('books')->first();
    expect(round($bookData->avg_rating, 1))->toBe(8.0);
    expect($bookData->total_votes)->toBe(3);
});

test('it displays trending indicator data', function () {
    $book = Book::factory()->create();
    Rating::factory()->create([
        'book_id' => $book->id,
        'rating' => 9,
        'created_at' => now()->subDays(3)
    ]);
    Rating::factory()->create([
        'book_id' => $book->id,
        'rating' => 6,
        'created_at' => now()->subDays(10)
    ]);

    $response = $this->get(route('books.index'));
    $response->assertStatus(200);
    $bookData = $response->viewData('books')->first();

    expect($bookData->recent_rating)->not()->toBeNull();
    expect($bookData->previous_rating)->not()->toBeNull();
    expect($bookData->recent_rating)->toBeGreaterThan($bookData->previous_rating);
});

test('it handles books without ratings', function () {
    Book::factory()->create(['title' => 'No Ratings Book']);

    $response = $this->get(route('books.index'));
    $response->assertStatus(200);
    expect($response->viewData('books')->count())->toBeGreaterThan(0);
});

test('it combines multiple filters', function () {
    $author = Author::factory()->create();
    $category = Category::factory()->create();

    $book1 = Book::factory()->create([
        'author_id' => $author->id,
        'publication_year' => 2021,
        'availability_status' => 'available',
    ]);
    $book1->categories()->attach($category->id);
    Rating::factory()->create(['book_id' => $book1->id, 'rating' => 8]);

    $response = $this->get(route('books.index', [
        'author_id' => $author->id,
        'categories' => [$category->id],
        'year_from' => 2021,
        'availability' => 'available',
        'rating_from' => 7,
    ]));

    $response->assertStatus(200);
});

test('it provides filter options in view', function () {
    Author::factory()->count(3)->create();
    Category::factory()->count(5)->create();
    Book::factory()->create(['store_location' => 'Section A']);
    Book::factory()->create(['store_location' => 'Section B']);

    $response = $this->get(route('books.index'));

    $response->assertStatus(200);
    expect($response->viewData('authors'))->toHaveCount(3);
    expect($response->viewData('categories'))->toHaveCount(5);
    expect($response->viewData('locations'))->toHaveCount(2);
    expect($response->viewData('years'))->toBeArray();
});

test('it maintains query parameters in pagination', function () {
    $author = Author::factory()->create();
    Book::factory()->count(60)->create(['author_id' => $author->id]);

    $response = $this->get(route('books.index', [
        'author_id' => $author->id,
        'sort' => 'alphabetical',
        'page' => 2,
    ]));

    $response->assertStatus(200);
    $books = $response->viewData('books');
    $paginationLinks = $books->appends(request()->query())->links()->toHtml();

    expect($paginationLinks)->toContain('author_id=' . $author->id);
    expect($paginationLinks)->toContain('sort=alphabetical');
});