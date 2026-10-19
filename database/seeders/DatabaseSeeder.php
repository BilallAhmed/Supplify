<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Store Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        User::factory()->customer()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $this->call([
            CatalogSeeder::class,
            CmsSeeder::class,
            BlogSeeder::class,
            CouponSeeder::class,
        ]);
    }
}
