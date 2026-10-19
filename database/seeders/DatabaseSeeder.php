<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Avoid User::factory() / fake() — Faker is require-dev and missing on production (--no-dev).
        $this->seedUser([
            'name' => 'Store Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        $this->seedUser([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
            'role' => UserRole::Customer,
        ]);

        $this->call([
            CatalogSeeder::class,
            CmsSeeder::class,
            BlogSeeder::class,
            CouponSeeder::class,
        ]);
    }

    /**
     * @param  array{name: string, email: string, password: string, role: UserRole}  $attrs
     */
    private function seedUser(array $attrs): void
    {
        $user = User::query()->firstOrNew(['email' => $attrs['email']]);
        $user->forceFill([
            'name' => $attrs['name'],
            'password' => $attrs['password'],
            'role' => $attrs['role'],
            'email_verified_at' => now(),
            'is_active' => true,
        ])->save();
    }
}
