<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AuthorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $totalAuthors = 1000;

        logger("Seeding {$totalAuthors} authors using factory...");

        // generate using factory
        Author::factory()->count($totalAuthors)->create();

        logger("Author seeding completed successfully!");
    }
}
