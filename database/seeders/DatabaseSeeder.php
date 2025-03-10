<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory(20)->create();
        \App\Models\Category::factory(20)->create();
        \App\Models\Product::factory(20)->create();
        \App\Models\Supplier::factory(20)->create();
        \App\Models\Customer::factory(20)->create();
        \App\Models\Sale::factory(20)->create();
        \App\Models\SaleItem::factory(20)->create();
    }
}
