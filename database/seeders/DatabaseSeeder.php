<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'username' => 'admin123',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
        ]);

        User::create([
            'username' => 'user1',
            'email' => 'user1@gmail.com',
            'password' => bcrypt('user123'),
            'role' => 'user',
        ]);

        $this->call([
            AuthorSeeder::class,
            CategorySeeder::class,
            BookSeeder::class,
            RatingSeeder::class,
        ]);
    }
}
