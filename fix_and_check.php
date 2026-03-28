<?php
// fix_and_check.php - Complete diagnostic and fix script for Windows
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RoomType;
use App\Models\Room;
use App\Models\Inventory;
use App\Models\Discount;
use App\Models\RatePlan;
use App\Models\RatePlanDiscount;
use App\Services\RoomAvailabilityService;
use App\Services\PricingCalculator;
use Carbon\Carbon;

echo "========================================\n";
echo "HOTEL BOOKING SYSTEM - DIAGNOSTIC TOOL\n";
echo "========================================\n\n";

// Function to check if Laravel is running
function isLaravelRunning() {
    $sock = @fsockopen('localhost', 8000);
    if ($sock) {
        fclose($sock);
        return true;
    }
    return false;
}

echo "1. Checking Laravel Server\n";
echo str_repeat("-", 40) . "\n";
if (isLaravelRunning()) {
    echo "✅ Laravel server is running on port 8000\n";
} else {
    echo "⚠️  Laravel server is NOT running\n";
    echo "   Start it with: php artisan serve\n";
}
echo "\n";

// Check and create room types (Updated for Round 2)
echo "2. Checking Room Types (Round 2 - Variable Occupancy)\n";
echo str_repeat("-", 40) . "\n";

$standard = RoomType::where('slug', 'standard')->first();
$deluxe = RoomType::where('slug', 'deluxe')->first();

if (!$standard) {
    echo "Creating Standard room type...\n";
    $standard = RoomType::create([
        'name' => 'Standard Room',
        'slug' => 'standard',
        'description' => 'Comfortable standard room with essential amenities',
        'max_occupancy' => 3,
        'min_occupancy' => 1,
        'base_price' => 100,
    ]);
    echo "✅ Standard room type created (ID: {$standard->id}, Max Occupancy: 3)\n";
} else {
    // Update existing Standard room with min_occupancy if missing
    if (!$standard->min_occupancy) {
        $standard->update(['min_occupancy' => 1]);
    }
    // Update max_occupancy to 3 if different
    if ($standard->max_occupancy != 3) {
        $standard->update(['max_occupancy' => 3]);
    }
    echo "✅ Standard room exists (ID: {$standard->id}, Max: {$standard->max_occupancy})\n";
}

if (!$deluxe) {
    echo "Creating Deluxe room type...\n";
    $deluxe = RoomType::create([
        'name' => 'Deluxe Room',
        'slug' => 'deluxe',
        'description' => 'Spacious deluxe room with premium amenities',
        'max_occupancy' => 4,
        'min_occupancy' => 1,
        'base_price' => 150,
    ]);
    echo "✅ Deluxe room type created (ID: {$deluxe->id}, Max Occupancy: 4)\n";
} else {
    // Update existing Deluxe room with new max_occupancy
    if (!$deluxe->min_occupancy) {
        $deluxe->update(['min_occupancy' => 1]);
    }
    if ($deluxe->max_occupancy != 4) {
        $deluxe->update(['max_occupancy' => 4]);
        echo "✅ Deluxe room updated to max occupancy 4\n";
    }
    echo "✅ Deluxe room exists (ID: {$deluxe->id}, Max: {$deluxe->max_occupancy})\n";
}
echo "\n";

// Check and create rate plans (NEW for Round 2)
echo "3. Checking Rate Plans (Round 2 - Multiple Rate Plans)\n";
echo str_repeat("-", 40) . "\n";

$ratePlansData = [
    ['room_type_id' => $standard->id, 'code' => 'EP', 'name' => 'Room Only', 'meal_charge_per_night' => 0, 'description' => 'Room only, no meals included'],
    ['room_type_id' => $standard->id, 'code' => 'CP', 'name' => 'Breakfast Included', 'meal_charge_per_night' => 25, 'description' => 'Includes breakfast'],
    ['room_type_id' => $deluxe->id, 'code' => 'CP', 'name' => 'Breakfast Included', 'meal_charge_per_night' => 30, 'description' => 'Includes breakfast'],
    ['room_type_id' => $deluxe->id, 'code' => 'MAP', 'name' => 'All Meals Included', 'meal_charge_per_night' => 75, 'description' => 'Includes breakfast, lunch, and dinner'],
];

$ratePlanCount = 0;
foreach ($ratePlansData as $rpData) {
    $exists = RatePlan::where('room_type_id', $rpData['room_type_id'])
        ->where('code', $rpData['code'])
        ->first();
    
    if (!$exists) {
        RatePlan::create($rpData);
        echo "✅ Created {$rpData['code']} - {$rpData['name']}\n";
        $ratePlanCount++;
    } else {
        echo "✅ {$rpData['code']} exists for {$exists->roomType->name}\n";
    }
}
echo "Total Rate Plans: " . RatePlan::count() . "\n\n";

// Check and create rooms
echo "4. Checking Rooms\n";
echo str_repeat("-", 40) . "\n";

foreach ([$standard, $deluxe] as $roomType) {
    $roomCount = Room::where('room_type_id', $roomType->id)->count();
    if ($roomCount < 5) {
        echo "Creating missing rooms for {$roomType->name}...\n";
        Room::where('room_type_id', $roomType->id)->delete();
        
        $prefix = $roomType->slug === 'standard' ? 'STD' : 'DLX';
        for ($i = 1; $i <= 5; $i++) {
            Room::create([
                'room_type_id' => $roomType->id,
                'room_number' => "{$prefix}-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]);
        }
        echo "✅ Created 5 rooms for {$roomType->name}\n";
    } else {
        echo "✅ {$roomType->name}: {$roomCount} rooms exist\n";
    }
}
echo "\n";

// Check and create inventory (Updated for Rate Plans)
echo "5. Checking Inventory (Rate Plan Specific)\n";
echo str_repeat("-", 40) . "\n";

$startDate = Carbon::today();
$endDate = Carbon::today()->addDays(30);
$ratePlansList = RatePlan::all();
$totalRatePlans = $ratePlansList->count();

$inventoryCount = Inventory::whereBetween('date', [$startDate, $endDate])->count();
$expectedCount = $totalRatePlans * 31;

if ($inventoryCount < $expectedCount) {
    echo "Creating inventory for next 30 days for all rate plans...\n";
    Inventory::truncate();
    
    $current = $startDate->copy();
    $created = 0;
    
    while ($current <= $endDate) {
        foreach ($ratePlansList as $ratePlan) {
            $basePrice = $ratePlan->roomType->base_price;
            $price = getDynamicPrice($basePrice, $current);
            
            Inventory::create([
                'room_type_id' => $ratePlan->room_type_id,
                'rate_plan_id' => $ratePlan->id,
                'date' => $current->format('Y-m-d'),
                'total_rooms' => 5,
                'booked_rooms' => rand(0, 2),
                'price' => $price,
            ]);
            $created++;
        }
        $current->addDay();
    }
    echo "✅ Created {$created} inventory records\n";
} else {
    echo "✅ Inventory exists ({$inventoryCount} records)\n";
}
echo "\n";

// Check and create general discounts (Long Stay & Last Minute)
echo "6. Checking General Discounts (Long Stay & Last Minute)\n";
echo str_repeat("-", 40) . "\n";

$discountCount = Discount::count();
if ($discountCount == 0) {
    echo "Creating general discounts...\n";
    
    // Long stay discounts for Standard
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
    
    // Long stay discounts for Deluxe
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
    
    echo "✅ Created " . Discount::count() . " general discounts\n";
} else {
    echo "✅ General discounts exist ({$discountCount} records)\n";
}
echo "\n";

// Check and create rate plan specific discounts (Early Bird)
echo "7. Checking Rate Plan Specific Discounts (Early Bird)\n";
echo str_repeat("-", 40) . "\n";

$ratePlanDiscountCount = RatePlanDiscount::count();
if ($ratePlanDiscountCount == 0) {
    echo "Creating rate plan specific discounts...\n";
    
    $allRatePlans = RatePlan::all();
    $created = 0;
    
    foreach ($allRatePlans as $ratePlan) {
        // Early bird discount (rate plan specific)
        $earlyBirdPercentage = $ratePlan->code === 'EP' ? 5 : 10;
        
        RatePlanDiscount::create([
            'rate_plan_id' => $ratePlan->id,
            'discount_type' => 'early_bird',
            'min_nights' => null,
            'max_nights' => null,
            'days_before_checkin' => 7,
            'discount_percentage' => $earlyBirdPercentage,
            'is_active' => true,
        ]);
        $created++;
    }
    
    echo "✅ Created {$created} rate plan specific discounts\n";
} else {
    echo "✅ Rate plan discounts exist ({$ratePlanDiscountCount} records)\n";
}
echo "\n";

// Show inventory preview with rate plans
echo "8. Inventory Preview (Next 5 days with Rate Plans)\n";
echo str_repeat("-", 60) . "\n";

$checkDate = Carbon::tomorrow();
for ($i = 0; $i < 5; $i++) {
    $date = $checkDate->copy()->addDays($i);
    $dateString = $date->format('Y-m-d');
    
    echo "\n📅 Date: {$dateString}\n";
    echo str_repeat("-", 60) . "\n";
    
    $inventoryItems = Inventory::where('date', $dateString)
        ->with(['roomType', 'ratePlan'])
        ->get();
    
    if ($inventoryItems->isEmpty()) {
        echo "  ❌ NO INVENTORY DATA\n";
        continue;
    }
    
    printf("  %-15s %-12s %-8s %-8s %-8s %-10s\n", 
        "Room Type", "Rate Plan", "Total", "Booked", "Avail", "Price");
    echo "  " . str_repeat("-", 60) . "\n";
    
    foreach ($inventoryItems as $item) {
        $available = $item->total_rooms - $item->booked_rooms;
        $status = $available > 0 ? "✅ {$available}" : "❌ 0";
        printf("  %-15s %-12s %-8d %-8d %-8s $%-9d\n", 
            $item->roomType->name,
            $item->ratePlan->code,
            $item->total_rooms,
            $item->booked_rooms,
            $status,
            $item->price
        );
    }
}
echo "\n";

// Test search
echo "9. Testing Search (Multiple Rate Plans)\n";
echo str_repeat("-", 60) . "\n";

try {
    $pricingCalculator = new PricingCalculator();
    $availabilityService = new RoomAvailabilityService($pricingCalculator);

    $testCheckIn = Carbon::tomorrow();
    $testCheckOut = Carbon::tomorrow()->addDays(3);

    echo "Search Criteria:\n";
    echo "  Check-in: {$testCheckIn->format('Y-m-d')}\n";
    echo "  Check-out: {$testCheckOut->format('Y-m-d')}\n";
    echo "  Nights: {$testCheckIn->diffInDays($testCheckOut)}\n";
    echo "  Adults: 2\n\n";

    $results = $availabilityService->getAvailableRooms(
        $testCheckIn,
        $testCheckOut,
        2,
        null  // null = show all rate plans
    );

    echo "Search Results:\n";
    echo "  Total Options Found: {$results['total_results']}\n";

    if ($results['total_results'] > 0) {
        foreach ($results['available_options'] as $option) {
            echo "\n  🏨 {$option['room_type']['name']} - {$option['rate_plan']['code']} ({$option['rate_plan']['name']})\n";
            echo "    Available Rooms: {$option['availability']['available_rooms']}\n";
            echo "    Price Breakdown:\n";
            echo "      Room Subtotal: \${$option['pricing']['breakdown']['room_subtotal']}\n";
            echo "      Meal Plan Charge: +\${$option['pricing']['breakdown']['meal_plan_charge']}\n";
            echo "      Discount: -\${$option['pricing']['breakdown']['discount']}\n";
            echo "      Total: \${$option['pricing']['breakdown']['total']}\n";
            echo "    Average Nightly Rate: \${$option['pricing']['nightly_rate']['average']}\n";
            
            if (!empty($option['pricing']['discounts']['applied'])) {
                echo "    Discounts Applied:\n";
                foreach ($option['pricing']['discounts']['applied'] as $discount) {
                    echo "      - {$discount['description']}: -\${$discount['amount']}\n";
                }
                echo "      Total Saved: \${$option['pricing']['discounts']['total_saved']}\n";
            }
        }
    } else {
        echo "\n  No options available\n";
        echo "\n  Possible issues:\n";
        echo "  1. Check if inventory exists for the dates\n";
        echo "  2. Check if rooms are fully booked\n";
        echo "  3. Check if adult count exceeds max occupancy\n";
        echo "     - Standard Room: max 3 adults\n";
        echo "     - Deluxe Room: max 4 adults\n";
    }
} catch (\Exception $e) {
    echo "❌ Error during search: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "✅ Diagnostic Complete!\n";
echo "========================================\n";

// Helper function for dynamic pricing
function getDynamicPrice($basePrice, $date) {
    $price = $basePrice;
    if ($date->isFriday() || $date->isSaturday()) {
        $price = $price * 1.2;
    }
    if (($date->month == 12 && $date->day >= 15) || ($date->month == 1 && $date->day <= 15)) {
        $price = $price * 1.3;
    }
    return (int) round($price);
}