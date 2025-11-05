<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $totalCategories = 3000;
        $batchSize = 500;

        logger("Seeding {$totalCategories} categories using factory...");

        collect(range(1, ceil($totalCategories / $batchSize)))->each(function ($chunk) use ($batchSize) {
            Category::factory()->count($batchSize)->create();
            logger("Inserted {$chunk} batch(es) of categories...");
            gc_collect_cycles();
        });

        logger("Category seeding completed successfully!");
    }
}