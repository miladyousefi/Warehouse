<?php

namespace Database\Seeders;

use App\Models\RestaurantTable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RestaurantTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            ['table_number' => 'T1', 'name' => 'Table 1', 'capacity' => 4, 'section' => 'indoor'],
            ['table_number' => 'T2', 'name' => 'Table 2', 'capacity' => 4, 'section' => 'indoor'],
            ['table_number' => 'T3', 'name' => 'Table 3', 'capacity' => 2, 'section' => 'indoor'],
            ['table_number' => 'T4', 'name' => 'Table 4', 'capacity' => 6, 'section' => 'indoor'],
            ['table_number' => 'T5', 'name' => 'Table 5', 'capacity' => 8, 'section' => 'indoor'],
            ['table_number' => 'T6', 'name' => 'Table 6', 'capacity' => 4, 'section' => 'outdoor'],
            ['table_number' => 'T7', 'name' => 'Table 7', 'capacity' => 4, 'section' => 'outdoor'],
            ['table_number' => 'T8', 'name' => 'Table 8', 'capacity' => 6, 'section' => 'outdoor'],
            ['table_number' => 'VIP1', 'name' => 'VIP Room 1', 'capacity' => 10, 'section' => 'vip'],
            ['table_number' => 'VIP2', 'name' => 'VIP Room 2', 'capacity' => 12, 'section' => 'vip'],
        ];

        foreach ($tables as $table) {
            RestaurantTable::create($table);
        }
    }
}
