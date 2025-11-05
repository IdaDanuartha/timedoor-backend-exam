<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rating>
 */
class RatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ratingValue = fake()->randomElement([
            1, 2, 3, 4, 5, 6, 6, 7, 7, 7, 8, 8, 8, 8, 9, 9, 9, 10
        ]);
        
        $daysAgo = rand(0, 90);
        $createdAt = now()->subDays($daysAgo);
        
        return [
            'book_id' => Book::inRandomOrder()->first()?->id ?? Book::factory(),
            'user_identifier' => fake()->uuid(),
            'rating' => $ratingValue,
            'review' => rand(0, 10) > 7 ? fake()->sentence(rand(5, 15)) : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * Indicate a high rating (8-10).
     */
    public function highRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->numberBetween(8, 10),
        ]);
    }

    /**
     * Indicate a low rating (1-4).
     */
    public function lowRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->numberBetween(1, 4),
        ]);
    }

    /**
     * Indicate a rating with review.
     */
    public function withReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'review' => fake()->paragraph(rand(1, 3)),
        ]);
    }

    /**
     * Set specific book for the rating.
     */
    public function forBook(int $bookId): static
    {
        return $this->state(fn (array $attributes) => [
            'book_id' => $bookId,
        ]);
    }

    /**
     * Set recent rating (within last 7 days).
     */
    public function recent(): static
    {
        $daysAgo = rand(0, 7);
        $createdAt = now()->subDays($daysAgo);
        
        return $this->state(fn (array $attributes) => [
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}