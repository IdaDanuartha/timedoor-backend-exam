<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $baseCategories = [
            'Fiction', 'Non-Fiction', 'Mystery', 'Thriller', 'Romance', 'Science Fiction',
            'Fantasy', 'Biography', 'History', 'Self-Help', 'Business', 'Psychology',
            'Philosophy', 'Religion', 'Science', 'Technology', 'Travel', 'Cooking',
            'Art', 'Music', 'Sports', 'Health', 'Fitness', 'Parenting', 'Education',
            'Poetry', 'Drama', 'Horror', 'Adventure', 'Crime', 'Humor', 'Comics',
        ];
        
        static $counter = 0;
        
        if ($counter < count($baseCategories)) {
            $name = $baseCategories[$counter];
            $counter++;
        } else {
            $name = ucwords(fake()->words(rand(1, 3), true) . ' ' . 
                   fake()->randomElement(['Books', 'Literature', 'Stories', 'Collection', 'Series', 'Genre']));
        }
        
        return [
            'name' => $name,
            'description' => fake()->sentence(10),
        ];
    }
}