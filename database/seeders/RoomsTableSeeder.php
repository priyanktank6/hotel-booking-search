<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\RoomType;

class RoomsTableSeeder extends Seeder
{
    public function run(): void
    {
        $standard = RoomType::where('slug', 'standard')->first();
        $deluxe = RoomType::where('slug', 'deluxe')->first();
        
        if (!$standard || !$deluxe) {
            $this->command->error('Room types not found!');
            return;
        }
        
        // Delete existing rooms
        Room::truncate();
        
        // Create 5 standard rooms
        for ($i = 1; $i <= 5; $i++) {
            Room::create([
                'room_type_id' => $standard->id,
                'room_number' => 'STD-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]);
        }
        
        // Create 5 deluxe rooms
        for ($i = 1; $i <= 5; $i++) {
            Room::create([
                'room_type_id' => $deluxe->id,
                'room_number' => 'DLX-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]);
        }
        
        $this->command->info('Created 10 rooms (5 Standard, 5 Deluxe)');
    }
}