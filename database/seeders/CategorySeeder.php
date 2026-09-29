<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Technology', 'description' => 'Software, tools, and the occasional hot take on frameworks.'],
            ['name' => 'Design', 'description' => 'Typography, layout, colour, and the reasoning behind design decisions.'],
            ['name' => 'Business', 'description' => 'Strategy, growth, and running a small team well.'],
            ['name' => 'Tutorials', 'description' => 'Step-by-step, practical walkthroughs.'],
            ['name' => 'Life & Work', 'description' => 'Habits, focus, and working sustainably.'],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
