<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Discount;
use App\Models\RoomType;

class DiscountsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $standard = RoomType::where('slug', 'standard')->first();
        $deluxe = RoomType::where('slug', 'deluxe')->first();
        
        // Long stay discounts
        Discount::create([
            'room_type_id' => $standard->id,
            'type' => 'long_stay',
            'min_nights' => 3,
            'max_nights' => 6,
            'days_before_checkin' => null,
            'discount_percentage' => 10,
            'is_active' => true,
        ]);
        
        Discount::create([
            'room_type_id' => $standard->id,
            'type' => 'long_stay',
            'min_nights' => 7,
            'max_nights' => null,
            'days_before_checkin' => null,
            'discount_percentage' => 15,
            'is_active' => true,
        ]);
        
        Discount::create([
            'room_type_id' => $deluxe->id,
            'type' => 'long_stay',
            'min_nights' => 3,
            'max_nights' => 6,
            'days_before_checkin' => null,
            'discount_percentage' => 12,
            'is_active' => true,
        ]);
        
        Discount::create([
            'room_type_id' => $deluxe->id,
            'type' => 'long_stay',
            'min_nights' => 7,
            'max_nights' => null,
            'days_before_checkin' => null,
            'discount_percentage' => 18,
            'is_active' => true,
        ]);
        
        // Last minute discounts
        Discount::create([
            'room_type_id' => $standard->id,
            'type' => 'last_minute',
            'min_nights' => null,
            'max_nights' => null,
            'days_before_checkin' => 3,
            'discount_percentage' => 20,
            'is_active' => true,
        ]);
        
        Discount::create([
            'room_type_id' => $deluxe->id,
            'type' => 'last_minute',
            'min_nights' => null,
            'max_nights' => null,
            'days_before_checkin' => 3,
            'discount_percentage' => 25,
            'is_active' => true,
        ]);
    }
}
