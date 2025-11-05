<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        
        $faker = Faker::create();
        $batchSize = 5000;
        $totalBooks = 100000;
        
        $authorIds = DB::table('authors')->pluck('id')->toArray();
        $categoryIds = DB::table('categories')->pluck('id')->toArray();
        
        $statuses = ['available', 'rented', 'reserved'];
        
        $publishers = array_map(fn() => $faker->company(), range(1, 100));
        $years = range(1950, 2024);
        
        logger("Starting seeding {$totalBooks} books with batch size {$batchSize}...");
        
        DB::transaction(function () use ($faker, $batchSize, $totalBooks, $authorIds, $categoryIds, $statuses, $publishers, $years) {
            
            for ($i = 0; $i < $totalBooks / $batchSize; $i++) {
                $books = [];
                $bookCategories = [];
                $startId = $i * $batchSize + 1;
                
                for ($j = 0; $j < $batchSize; $j++) {
                    $bookId = $startId + $j;
                    
                    $books[] = [
                        'title' => $faker->sentence(rand(2, 6)),
                        'isbn' => $faker->isbn13(),
                        'author_id' => $authorIds[array_rand($authorIds)],
                        'publisher' => $publishers[array_rand($publishers)],
                        'publication_year' => $years[array_rand($years)],
                        'availability_status' => $statuses[array_rand($statuses)],
                        'store_location' => $faker->word(),
                        'description' => $faker->paragraph(1),
                        'price' => round(mt_rand(500, 10000) / 100, 2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    $numCategories = mt_rand(1, 5);
                    $shuffledCategories = $categoryIds;
                    shuffle($shuffledCategories);
                    $selectedCategories = array_slice($shuffledCategories, 0, $numCategories);
                    
                    foreach ($selectedCategories as $categoryId) {
                        $bookCategories[] = [
                            'book_id' => $bookId,
                            'category_id' => $categoryId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                
                collect($books)->chunk(1000)->each(function ($chunk) {
                    DB::table('books')->insert($chunk->toArray());
                });
                
                collect($bookCategories)->chunk(2000)->each(function ($chunk) {
                    DB::table('book_category')->insert($chunk->toArray());
                });
                
                // Clear memory
                unset($books, $bookCategories);
                
                $progress = ($i + 1) * $batchSize;
                $percentage = round(($progress / $totalBooks) * 100, 2);
                logger("Inserted {$progress}/{$totalBooks} books ({$percentage}%)");
                
                // Force garbage collection setiap batch
                if ($i % 5 == 0) {
                    gc_collect_cycles();
                }
            }
        });
        
        logger("Books seeding completed successfully!");
    }
}
