<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RatePlan;
use App\Models\RoomType;

class RatePlansTableSeeder extends Seeder
{
    public function run(): void
    {
        $standard = RoomType::where('slug', 'standard')->first();
        $deluxe = RoomType::where('slug', 'deluxe')->first();
        
        // Standard Room Rate Plans
        RatePlan::create([
            'room_type_id' => $standard->id,
            'code' => 'EP',
            'name' => 'Room Only',
            'description' => 'Room only, no meals included',
            'meal_charge_per_night' => 0,
            'is_active' => true,
        ]);
        
        RatePlan::create([
            'room_type_id' => $standard->id,
            'code' => 'CP',
            'name' => 'Breakfast Included',
            'description' => 'Includes breakfast',
            'meal_charge_per_night' => 25,
            'is_active' => true,
        ]);
        
        // Deluxe Room Rate Plans
        RatePlan::create([
            'room_type_id' => $deluxe->id,
            'code' => 'CP',
            'name' => 'Breakfast Included',
            'description' => 'Includes breakfast',
            'meal_charge_per_night' => 30,
            'is_active' => true,
        ]);
        
        RatePlan::create([
            'room_type_id' => $deluxe->id,
            'code' => 'MAP',
            'name' => 'All Meals Included',
            'description' => 'Includes breakfast, lunch, and dinner',
            'meal_charge_per_night' => 75,
            'is_active' => true,
        ]);
    }
}