<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\RoomType;
use Carbon\Carbon;

class InventoryTableSeeder extends Seeder
{
    public function run(): void
    {
        // Get room types
        $standard = RoomType::where('slug', 'standard')->first();
        $deluxe = RoomType::where('slug', 'deluxe')->first();
        
        if (!$standard || !$deluxe) {
            $this->command->error('Room types not found! Please run RoomTypesTableSeeder first.');
            return;
        }
        
        // Clear existing inventory
        Inventory::truncate();
        
        // Generate inventory for next 90 days
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(90);
        
        $current = $startDate->copy();
        $createdCount = 0;
        
        while ($current->lte($endDate)) {
            $dateString = $current->format('Y-m-d');
            
            // Standard room inventory - ensure at least 2-3 rooms available
            $standardBooked = rand(0, 2); // Random between 0-2 booked, leaving 3-5 available
            $standardPrice = $this->getDynamicPrice(100, $current);
            
            Inventory::create([
                'room_type_id' => $standard->id,
                'date' => $dateString,
                'total_rooms' => 5,
                'booked_rooms' => $standardBooked,
                'price' => $standardPrice,
            ]);
            
            // Deluxe room inventory - ensure at least 2-3 rooms available
            $deluxeBooked = rand(0, 2); // Random between 0-2 booked, leaving 3-5 available
            $deluxePrice = $this->getDynamicPrice(150, $current);
            
            Inventory::create([
                'room_type_id' => $deluxe->id,
                'date' => $dateString,
                'total_rooms' => 5,
                'booked_rooms' => $deluxeBooked,
                'price' => $deluxePrice,
            ]);
            
            $createdCount++;
            $current->addDay();
        }
        
        $this->command->info("Created {$createdCount} days of inventory for both room types");
        $this->command->info("Total inventory records: " . Inventory::count());
    }
    
    private function getDynamicPrice($basePrice, Carbon $date): int
    {
        $price = $basePrice;
        
        // Weekend pricing (Friday and Saturday)
        if ($date->isFriday() || $date->isSaturday()) {
            $price = $price * 1.2;
        }
        
        // Peak season (December 15 - January 15)
        if (($date->month == 12 && $date->day >= 15) || ($date->month == 1 && $date->day <= 15)) {
            $price = $price * 1.3;
        }
        
        return (int) round($price);
    }
}