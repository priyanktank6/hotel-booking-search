<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoomTypesTableSeeder::class,
            RoomsTableSeeder::class,
            RatePlansTableSeeder::class,
            DiscountsTableSeeder::class,
            RatePlanDiscountsTableSeeder::class,
            InventoryTableSeeder::class,
        ]);
    }
}
