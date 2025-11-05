<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $faker = Faker::create();
        $batchSize = 10000;
        $totalRatings = 500000;
        
        $bookIds = DB::table('books')->pluck('id')->toArray();
        $userIds = DB::table('users')->pluck('id')->toArray();
        
        $bookCount = count($bookIds);
        $userCount = count($userIds);

        $ratingPool = [1, 2, 3, 4, 5, 6, 6, 7, 7, 7, 8, 8, 8, 8, 9, 9, 9, 10];
        
        $reviewPool = array_map(fn() => $faker->sentence(rand(5, 15)), range(1, 500));
        $reviewPoolSize = count($reviewPool);
        
        logger("Starting seeding {$totalRatings} ratings with batch size {$batchSize}...");

        DB::transaction(function () use (
            $batchSize, 
            $totalRatings, 
            $bookIds, 
            $userIds, 
            $bookCount, 
            $userCount,
            $ratingPool,
            $reviewPool,
            $reviewPoolSize
        ) {
            $now = now();
            
            for ($i = 0; $i < $totalRatings / $batchSize; $i++) {
                $ratings = [];
                
                for ($j = 0; $j < $batchSize; $j++) {
                    $daysAgo = mt_rand(0, 90);
                    $createdAt = $now->copy()->subDays($daysAgo);

                    $hasReview = mt_rand(1, 10) > 7;
                    
                    $ratings[] = [
                        'book_id' => $bookIds[mt_rand(0, $bookCount - 1)],
                        'user_identifier' => $userIds[mt_rand(0, $userCount - 1)],
                        'rating' => $ratingPool[array_rand($ratingPool)],
                        'review' => $hasReview ? $reviewPool[mt_rand(0, $reviewPoolSize - 1)] : null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];
                }
                
                // Insert dengan chunking untuk balance memory dan query
                collect($ratings)->chunk(2500)->each(function ($chunk) {
                    DB::table('ratings')->insert($chunk->toArray());
                });
                
                // Clear memory
                unset($ratings);
                
                $progress = ($i + 1) * $batchSize;
                $percentage = round(($progress / $totalRatings) * 100, 2);
                logger("Inserted {$progress}/{$totalRatings} ratings ({$percentage}%)");
                
                // Garbage collection setiap 3 batch
                if ($i % 3 == 0) {
                    gc_collect_cycles();
                }
            }
        });

        logger("Ratings seeding completed successfully!");
    }
}
