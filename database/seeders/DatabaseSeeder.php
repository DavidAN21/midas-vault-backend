<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============================
        // CREATE ADMIN
        // ============================
        $admin = User::create([
            'name' => 'Admin Midas',
            'email' => 'admin@midasvault.com',
            'password' => Hash::make('password123'),
            'role' => 'admin', // ADMIN TETAP ADMIN
            'phone' => '081234567890',
            'address' => 'Magelang, Jawa Tengah',
            'reputation_score' => 5.00,
        ]);

        // ============================
        // CREATE USERS (UMUM)
        // ============================
        $user1 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'phone' => '081234567891',
            'address' => 'Jl. Merdeka No. 123, Magelang',
            'reputation_score' => 4.8,
        ]);

        $user2 = User::create([
            'name' => 'Sari Indah',
            'email' => 'sari@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'phone' => '081234567892',
            'address' => 'Jl. Pahlawan No. 45, Magelang',
            'reputation_score' => 4.9,
        ]);

        $user3 = User::create([
            'name' => 'Rina Wijaya',
            'email' => 'rina@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'phone' => '081234567893',
            'address' => 'Jl. Sudirman No. 67, Magelang',
            'reputation_score' => 4.7,
        ]);

        $user4 = User::create([
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'phone' => '081234567894',
            'address' => 'Jl. Gatot Subroto No. 89, Magelang',
            'reputation_score' => 4.6,
        ]);

        // ============================
        // CREATE PRODUCT FOR USER1
        // ============================
        Product::create([
            'user_id' => $user1->id,
            'name' => 'Sepatu Sneakers Nike Air Max',
            'description' => 'Sepatu sneakers Nike Air Max kondisi sangat baik, hanya dipakai beberapa kali. Original dan nyaman digunakan.',
            'category' => 'Fashion',
            'condition' => 'excellent',
            'price' => 450000,
            'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500',
            'verified_at' => now(),
            'verified_by' => $admin->id,
            'verification_status' => 'approved',
            'allow_barter' => true,
            'barter_preferences' => 'Saya tertarik dengan elektronik atau sepatu merek lain',
            'allow_trade_in' => true,
            'trade_in_value' => 300000,
        ]);

        // ============================
        // CONSOLE OUTPUT
        // ============================
        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin: admin@midasvault.com / password123');
        $this->command->info('User1: budi@example.com / password123');
        $this->command->info('User2: sari@example.com / password123');
        $this->command->info('User3: rina@example.com / password123');
        $this->command->info('User4: ahmad@example.com / password123');
    }
}
