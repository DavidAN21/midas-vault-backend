<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ClearProductsSeeder extends Seeder
{
    public function run(): void
    {
        // Hapus semua produk kecuali dari admin (user_id = 1)
        Product::where('user_id', '!=', 1)->delete();
        
        // Atau hapus semua produk
        // Product::truncate();
        
        $this->command->info('✅ Produk demo berhasil dihapus!');
    }
}