<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GeneralCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            'name' => 'General',             // More specific than 'category'
            'slug' => 'general',             // SEO-friendly URL
            'description' => 'For listing general items',      // Description of the category
            'image' => 'categories/general.jpg',            // Renamed from 'img' for clarity
            'parent_id' => null,        // For sub-categories (nullable)
            'is_featured' => '1',      // Boolean to mark featured category
            'status' => 'active',           // 'active', 'inactive', etc.
        ]);
    }
}
