<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomType;

class RoomTypesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Delete existing to avoid duplicates
        RoomType::truncate();
        
        RoomType::create([
            'name' => 'Standard Room',
            'slug' => 'standard',
            'description' => 'Comfortable standard room with essential amenities including free WiFi, flat-screen TV, and private bathroom.',
            'max_occupancy' => 3,
            'base_price' => 100,
        ]);
        
        RoomType::create([
            'name' => 'Deluxe Room',
            'slug' => 'deluxe',
            'description' => 'Spacious deluxe room with premium amenities including king-size bed, city view, minibar, and complimentary breakfast.',
            'max_occupancy' => 3,
            'base_price' => 150,
        ]);
        
        $this->command->info('Room types created successfully');
    }
}