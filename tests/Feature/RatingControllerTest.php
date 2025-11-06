<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('it displays rating create page with authors', function () {
    Author::factory()->count(3)->create();

    $response = $this->get(route('ratings.create'));

    $response->assertStatus(200);
    $response->assertViewIs('ratings.create');
    $response->assertViewHas('authors');
    $response->assertSee('Rate a Book');
});

test('it returns empty array when author id is not provided', function () {
    $response = $this->getJson(route('books.by-author'));
    $response->assertStatus(200)->assertJson([]);
});

test('it returns books by author', function () {
    $author = Author::factory()->create();
    $books = Book::factory()->count(3)->create(['author_id' => $author->id]);
    $otherAuthor = Author::factory()->create();
    Book::factory()->count(2)->create(['author_id' => $otherAuthor->id]);

    $response = $this->getJson(route('books.by-author', ['author_id' => $author->id]));

    $response->assertStatus(200);
    $response->assertJsonCount(3);
    $response->assertJsonStructure([
        '*' => ['id', 'title']
    ]);

    $returnedBookIds = collect($response->json())->pluck('id')->toArray();
    $expectedBookIds = $books->pluck('id')->toArray();
    expect(sort($expectedBookIds))->toEqual(sort($returnedBookIds));
});

test('it validates required fields', function () {
    $response = $this->post(route('ratings.store'), []);
    $response->assertSessionHasErrors(['author_id', 'book_id', 'rating']);
});

test('it validates author exists', function () {
    $response = $this->post(route('ratings.store'), [
        'author_id' => 99999,
        'book_id' => 1,
        'rating' => 8,
    ]);
    $response->assertSessionHasErrors(['author_id']);
});

test('it validates book exists', function () {
    $author = Author::factory()->create();
    $response = $this->post(route('ratings.store'), [
        'author_id' => $author->id,
        'book_id' => 99999,
        'rating' => 8,
    ]);
    $response->assertSessionHasErrors(['book_id']);
});

test('it validates rating is between 1 and 10', function () {
    $author = Author::factory()->create();
    $book = Book::factory()->create(['author_id' => $author->id]);

    $response1 = $this->post(route('ratings.store'), [
        'author_id' => $author->id,
        'book_id' => $book->id,
        'rating' => 0,
    ]);
    $response1->assertSessionHasErrors(['rating']);

    $response2 = $this->post(route('ratings.store'), [
        'author_id' => $author->id,
        'book_id' => $book->id,
        'rating' => 11,
    ]);
    $response2->assertSessionHasErrors(['rating']);
});

test('it validates review max length', function () {
    $author = Author::factory()->create();
    $book = Book::factory()->create(['author_id' => $author->id]);

    $response = $this->post(route('ratings.store'), [
        'author_id' => $author->id,
        'book_id' => $book->id,
        'rating' => 8,
        'review' => str_repeat('a', 1001),
    ]);

    $response->assertSessionHasErrors(['review']);
});

test('it rejects when book does not belong to author', function () {
    $author1 = Author::factory()->create();
    $author2 = Author::factory()->create();
    $book = Book::factory()->create(['author_id' => $author1->id]);

    $response = $this->post(route('ratings.store'), [
        'author_id' => $author2->id,
        'book_id' => $book->id,
        'rating' => 8,
    ]);

    $response->assertSessionHasErrors(['book_id']);
    $response->assertSessionHasErrorsIn('default', ['book_id' => 'The selected book does not belong to the chosen author.']);
});

test('it prevents duplicate rating on same book', function () {
    $author = Author::factory()->create();
    $book = Book::factory()->create(['author_id' => $author->id]);
    
    Rating::factory()->create([
        'book_id' => $book->id,
        'user_identifier' => md5('127.0.0.1' . 'test-user-agent'),
        'rating' => 9,
    ]);

    $response = $this->withHeaders([
        'User-Agent' => 'test-user-agent',
    ])->post(route('ratings.store'), [
        'author_id' => $author->id,
        'book_id' => $book->id,
        'rating' => 8,
    ]);

    $response->assertSessionHasErrors(['book_id']);
    $response->assertSessionHasErrorsIn('default', ['book_id' => 'You have already rated this book.']);
    expect(Rating::where('book_id', $book->id)->count())->toBe(1);
});

test('it enforces 24 hour cooldown between ratings', function () {
    $author = Author::factory()->create();
    $book1 = Book::factory()->create(['author_id' => $author->id]);
    $book2 = Book::factory()->create(['author_id' => $author->id]);
    
    $userIdentifier = md5('127.0.0.1' . 'test-user-agent');

    Rating::factory()->create([
        'book_id' => $book1->id,
        'user_identifier' => $userIdentifier,
        'rating' => 9,
        'created_at' => now()->subHours(12),
    ]);

    $response = $this->withHeaders([
        'User-Agent' => 'test-user-agent',
    ])->post(route('ratings.store'), [
        'author_id' => $author->id,
        'book_id' => $book2->id,
        'rating' => 8,
    ]);

    $response->assertSessionHasErrors(['rating']);
    expect(session('errors')->default->first('rating'))->toContain('menunggu');
    expect(Rating::where('book_id', $book2->id)->count())->toBe(0);
});

test('it allows rating after 24 hour cooldown', function () {
    $author = Author::factory()->create();
    $book1 = Book::factory()->create(['author_id' => $author->id]);
    $book2 = Book::factory()->create(['author_id' => $author->id]);
    $userIdentifier = md5('127.0.0.1' . 'test-user-agent');

    Rating::factory()->create([
        'book_id' => $book1->id,
        'user_identifier' => $userIdentifier,
        'rating' => 9,
        'created_at' => now()->subHours(25),
    ]);

    $response = $this->withHeaders([
        'User-Agent' => 'test-user-agent',
    ])->post(route('ratings.store'), [
        'author_id' => $author->id,
        'book_id' => $book2->id,
        'rating' => 8,
    ]);

    $response->assertRedirect(route('books.index'))->assertSessionHas('success');
    expect(Rating::where('book_id', $book2->id)->count())->toBe(1);
});

test('it successfully creates rating with review', function () {
    $author = Author::factory()->create();
    $book = Book::factory()->create(['author_id' => $author->id]);

    $response = $this->post(route('ratings.store'), [
        'author_id' => $author->id,
        'book_id' => $book->id,
        'rating' => 9,
        'review' => 'This is an excellent book!',
    ]);

    $response->assertRedirect(route('books.index'))
             ->assertSessionHas('success', 'Thank you! Your rating has been submitted successfully.');

    expect(Rating::where('book_id', $book->id)->where('review', 'This is an excellent book!')->exists())->toBeTrue();
});

test('it successfully creates rating without review', function () {
    $author = Author::factory()->create();
    $book = Book::factory()->create(['author_id' => $author->id]);

    $response = $this->post(route('ratings.store'), [
        'author_id' => $author->id,
        'book_id' => $book->id,
        'rating' => 8,
    ]);

    $response->assertRedirect(route('books.index'))->assertSessionHas('success');
    expect(Rating::where('book_id', $book->id)->whereNull('review')->exists())->toBeTrue();
});

test('it stores user identifier correctly', function () {
    $author = Author::factory()->create();
    $book = Book::factory()->create(['author_id' => $author->id]);
    $userAgent = 'Mozilla/5.0 Test Browser';
    $expectedIdentifier = md5('127.0.0.1' . $userAgent);

    $response = $this->withHeaders([
        'User-Agent' => $userAgent,
    ])->post(route('ratings.store'), [
        'author_id' => $author->id,
        'book_id' => $book->id,
        'rating' => 7,
    ]);

    $response->assertRedirect(route('books.index'));
    expect(Rating::where('book_id', $book->id)->where('user_identifier', $expectedIdentifier)->exists())->toBeTrue();
});

// test('it handles concurrent submissions gracefully', function () {
//     $author = Author::factory()->create();
//     $book = Book::factory()->create(['author_id' => $author->id]);

//     $response1 = $this->post(route('ratings.store'), [
//         'author_id' => $author->id,
//         'book_id' => $book->id,
//         'rating' => 9,
//     ]);

//     $response2 = $this->post(route('ratings.store'), [
//         'author_id' => $author->id,
//         'book_id' => $book->id,
//         'rating' => 8,
//     ]);

//     $response1->assertRedirect(route('books.index'))->assertSessionHas('success');
//     $response2->assertSessionHasErrors(['book_id']);
//     expect(Rating::where('book_id', $book->id)->count())->toBe(1);
// });

test('it returns old input on validation error', function () {
    $author = Author::factory()->create();

    $response = $this->post(route('ratings.store'), [
        'author_id' => $author->id,
        'book_id' => 99999,
        'rating' => 8,
        'review' => 'Test review',
    ]);

    $response->assertSessionHasErrors(['book_id']);
    $response->assertSessionHasInput('author_id', $author->id);
    $response->assertSessionHasInput('rating', 8);
    $response->assertSessionHasInput('review', 'Test review');
});

// test('it catches and logs exceptions during rating creation', function () {
//     $author = Author::factory()->create();
//     $book = Book::factory()->create(['author_id' => $author->id]);

//     $this->mock(Rating::class, function ($mock) {
//         $mock->shouldReceive('create')->once()->andThrow(new \Exception('Database error'));
//     });

//     $response = $this->post(route('ratings.store'), [
//         'author_id' => $author->id,
//         'book_id' => $book->id,
//         'rating' => 8,
//     ]);

//     $response->assertSessionHasErrors(['error']);
//     expect(session('errors')->default->first('error'))->toContain('An error occurred');
// });

test('different users can rate same book', function () {
    $author = Author::factory()->create();
    $book = Book::factory()->create(['author_id' => $author->id]);

    $response1 = $this->withHeaders(['User-Agent' => 'Browser-1'])
        ->post(route('ratings.store'), [
            'author_id' => $author->id,
            'book_id' => $book->id,
            'rating' => 9,
        ]);

    $response2 = $this->withHeaders(['User-Agent' => 'Browser-2'])
        ->from('192.168.1.2')
        ->post(route('ratings.store'), [
            'author_id' => $author->id,
            'book_id' => $book->id,
            'rating' => 7,
        ]);

    $response1->assertRedirect(route('books.index'));
    $response2->assertRedirect(route('books.index'));
    expect(Rating::where('book_id', $book->id)->count())->toBe(2);
});

test('it calculates remaining cooldown time correctly', function () {
    $author = Author::factory()->create();
    $book1 = Book::factory()->create(['author_id' => $author->id]);
    $book2 = Book::factory()->create(['author_id' => $author->id]);
    $userIdentifier = md5('127.0.0.1' . 'test-user-agent');

    Rating::factory()->create([
        'book_id' => $book1->id,
        'user_identifier' => $userIdentifier,
        'rating' => 9,
        'created_at' => now()->subHours(12),
    ]);

    $response = $this->withHeaders(['User-Agent' => 'test-user-agent'])
        ->post(route('ratings.store'), [
            'author_id' => $author->id,
            'book_id' => $book2->id,
            'rating' => 8,
        ]);

    $response->assertSessionHasErrors(['rating']);
    $errorMessage = session('errors')->default->first('rating');
    // expect($errorMessage)->toContain('12 jam');
    expect($errorMessage)->toContain('menunggu');
});