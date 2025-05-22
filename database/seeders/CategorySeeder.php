<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default expense categories as specified in requirements
        $categories = [
            'Groceries',
            'Leisure',
            'Electronics',
            'Utilities',
            'Clothing',
            'Health',
            'Others'
        ];

        foreach ($categories as $categoryName) {
            Category::create(['name' => $categoryName]);
        }
    }
}
