<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $publishers = null;
        static $locations = null;
        
        if ($publishers === null) {
            $publishers = [];
            for ($i = 0; $i < 100; $i++) {
                $publishers[] = fake()->company();
            }
        }
        
        if ($locations === null) {
            $locations = ['Section A', 'Section B', 'Section C', 'Section D', 'Section E', 
                          'Warehouse 1', 'Warehouse 2', 'Storage Room', 'Main Floor', 'Second Floor'];
        }
        
        return [
            'title' => fake()->sentence(rand(2, 6)),
            'isbn' => fake()->unique()->isbn13(),
            'author_id' => Author::inRandomOrder()->first()?->id ?? Author::factory(),
            'publisher' => $publishers[array_rand($publishers)],
            'publication_year' => fake()->numberBetween(1950, 2024),
            'availability_status' => fake()->randomElement(['available', 'rented', 'reserved']),
            'store_location' => $locations[array_rand($locations)],
            'description' => fake()->paragraph(3),
            'price' => fake()->randomFloat(2, 5, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the book is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'availability_status' => 'available',
        ]);
    }

    /**
     * Indicate that the book is rented.
     */
    public function rented(): static
    {
        return $this->state(fn (array $attributes) => [
            'availability_status' => 'rented',
        ]);
    }

    /**
     * Indicate that the book is reserved.
     */
    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'availability_status' => 'reserved',
        ]);
    }

    /**
     * Set specific author for the book.
     */
    public function forAuthor(int $authorId): static
    {
        return $this->state(fn (array $attributes) => [
            'author_id' => $authorId,
        ]);
    }

    /**
     * Set specific publication year.
     */
    public function publishedIn(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'publication_year' => $year,
        ]);
    }
}