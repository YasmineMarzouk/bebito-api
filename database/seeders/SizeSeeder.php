<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sizes')->upsert([
            ['name' => '0-3months', 'sort_order' => 1, 'is_default' => true],
            ['name' => '3-6months', 'sort_order' => 2, 'is_default' => true],
            ['name' => '6-9months', 'sort_order' => 3, 'is_default' => false],
            ['name' => '6-12months', 'sort_order' => 4, 'is_default' => true],
            ['name' => '12-24months', 'sort_order' => 5, 'is_default' => true],
        ], ['name'], ['sort_order', 'is_default']);
    }
}
