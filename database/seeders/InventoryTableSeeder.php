<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\RoomType;
use App\Models\RatePlan;
use Carbon\Carbon;

class InventoryTableSeeder extends Seeder
{
    public function run(): void
    {
        // Get room types
        $standard = RoomType::where('slug', 'standard')->first();
        $deluxe = RoomType::where('slug', 'deluxe')->first();
        
        if (!$standard || !$deluxe) {
            $this->command->error('Room types not found! Run RoomTypesTableSeeder first.');
            return;
        }
        
        // Get all rate plans
        $ratePlans = RatePlan::all();
        
        if ($ratePlans->isEmpty()) {
            $this->command->error('Rate plans not found! Run RatePlansTableSeeder first.');
            return;
        }
        
        // Generate inventory for next 90 days
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(90);
        
        $current = $startDate->copy();
        $createdCount = 0;
        
        // Clear existing inventory
        Inventory::truncate();
        
        while ($current->lte($endDate)) {
            $dateString = $current->format('Y-m-d');
            
            // Create inventory for each rate plan
            foreach ($ratePlans as $ratePlan) {
                $basePrice = $ratePlan->roomType->base_price;
                $bookedRooms = rand(0, 2); // Random bookings for testing
                $dynamicPrice = $this->getDynamicPrice($basePrice, $current);
                
                Inventory::create([
                    'room_type_id' => $ratePlan->room_type_id,
                    'rate_plan_id' => $ratePlan->id,
                    'date' => $dateString,
                    'total_rooms' => 5,
                    'booked_rooms' => $bookedRooms,
                    'price' => $dynamicPrice,
                ]);
                $createdCount++;
            }
            
            $current->addDay();
        }
        
        $this->command->info("Created {$createdCount} inventory records for all rate plans");
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