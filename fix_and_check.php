<?php
// fix_and_check.php - Complete diagnostic and fix script for Windows
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RoomType;
use App\Models\Room;
use App\Models\Inventory;
use App\Models\Discount;
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

// Check and create room types
echo "2. Checking Room Types\n";
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
        'base_price' => 100,
    ]);
    echo "✅ Standard room type created (ID: {$standard->id})\n";
} else {
    echo "✅ Standard room type exists (ID: {$standard->id})\n";
}

if (!$deluxe) {
    echo "Creating Deluxe room type...\n";
    $deluxe = RoomType::create([
        'name' => 'Deluxe Room',
        'slug' => 'deluxe',
        'description' => 'Spacious deluxe room with premium amenities',
        'max_occupancy' => 3,
        'base_price' => 150,
    ]);
    echo "✅ Deluxe room type created (ID: {$deluxe->id})\n";
} else {
    echo "✅ Deluxe room type exists (ID: {$deluxe->id})\n";
}
echo "\n";

// Check and create rooms
echo "3. Checking Rooms\n";
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

// Check and create inventory
echo "4. Checking Inventory\n";
echo str_repeat("-", 40) . "\n";

$startDate = Carbon::today();
$endDate = Carbon::today()->addDays(30);
$inventoryCount = Inventory::whereBetween('date', [$startDate, $endDate])->count();

if ($inventoryCount < 60) { // 30 days * 2 room types = 60
    echo "Creating inventory for next 30 days...\n";
    Inventory::truncate();
    
    $current = $startDate->copy();
    $created = 0;
    
    while ($current <= $endDate) {
        $dateString = $current->format('Y-m-d');
        
        // Standard room
        Inventory::create([
            'room_type_id' => $standard->id,
            'date' => $dateString,
            'total_rooms' => 5,
            'booked_rooms' => rand(0, 2),
            'price' => getDynamicPrice(100, $current),
        ]);
        
        // Deluxe room
        Inventory::create([
            'room_type_id' => $deluxe->id,
            'date' => $dateString,
            'total_rooms' => 5,
            'booked_rooms' => rand(0, 2),
            'price' => getDynamicPrice(150, $current),
        ]);
        
        $created += 2;
        $current->addDay();
    }
    echo "✅ Created {$created} inventory records\n";
} else {
    echo "✅ Inventory exists ({$inventoryCount} records)\n";
}
echo "\n";

// Check and create discounts
echo "5. Checking Discounts\n";
echo str_repeat("-", 40) . "\n";

$discountCount = Discount::count();
if ($discountCount == 0) {
    echo "Creating discounts...\n";
    
    // Long stay discounts for Standard
    Discount::create([
        'room_type_id' => $standard->id,
        'type' => 'long_stay',
        'min_nights' => 3,
        'max_nights' => 6,
        'discount_percentage' => 10,
        'is_active' => true,
    ]);
    
    Discount::create([
        'room_type_id' => $standard->id,
        'type' => 'long_stay',
        'min_nights' => 7,
        'max_nights' => null,
        'discount_percentage' => 15,
        'is_active' => true,
    ]);
    
    // Long stay discounts for Deluxe
    Discount::create([
        'room_type_id' => $deluxe->id,
        'type' => 'long_stay',
        'min_nights' => 3,
        'max_nights' => 6,
        'discount_percentage' => 12,
        'is_active' => true,
    ]);
    
    Discount::create([
        'room_type_id' => $deluxe->id,
        'type' => 'long_stay',
        'min_nights' => 7,
        'max_nights' => null,
        'discount_percentage' => 18,
        'is_active' => true,
    ]);
    
    // Last minute discounts
    Discount::create([
        'room_type_id' => $standard->id,
        'type' => 'last_minute',
        'days_before_checkin' => 3,
        'discount_percentage' => 20,
        'is_active' => true,
    ]);
    
    Discount::create([
        'room_type_id' => $deluxe->id,
        'type' => 'last_minute',
        'days_before_checkin' => 3,
        'discount_percentage' => 25,
        'is_active' => true,
    ]);
    
    echo "✅ Created " . Discount::count() . " discounts\n";
} else {
    echo "✅ Discounts exist ({$discountCount} records)\n";
}
echo "\n";

// Show inventory preview
echo "6. Inventory Preview (Next 5 days)\n";
echo str_repeat("-", 40) . "\n";

$checkDate = Carbon::tomorrow();
for ($i = 0; $i < 5; $i++) {
    $date = $checkDate->copy()->addDays($i);
    $dateString = $date->format('Y-m-d');
    
    echo "\nDate: {$dateString}\n";
    
    foreach ([$standard, $deluxe] as $roomType) {
        $inventory = Inventory::where('room_type_id', $roomType->id)
            ->where('date', $dateString)
            ->first();
        
        if ($inventory) {
            $available = $inventory->total_rooms - $inventory->booked_rooms;
            echo "  {$roomType->name}: Total={$inventory->total_rooms}, Booked={$inventory->booked_rooms}, Available={$available}, Price=\${$inventory->price}\n";
        } else {
            echo "  {$roomType->name}: NO INVENTORY DATA\n";
        }
    }
}
echo "\n";

// Test search
echo "7. Testing Search\n";
echo str_repeat("-", 40) . "\n";

try {
    $pricingCalculator = new PricingCalculator();
    $availabilityService = new RoomAvailabilityService($pricingCalculator);

    $testCheckIn = Carbon::tomorrow();
    $testCheckOut = Carbon::tomorrow()->addDays(3);

    echo "Search Criteria:\n";
    echo "  Check-in: {$testCheckIn->format('Y-m-d')}\n";
    echo "  Check-out: {$testCheckOut->format('Y-m-d')}\n";
    echo "  Adults: 2\n";
    echo "  Meal Plan: room_only\n\n";

    $results = $availabilityService->getAvailableRooms(
        $testCheckIn,
        $testCheckOut,
        2,
        'room_only'
    );

    echo "Search Results:\n";
    echo "  Total Results: {$results['total_results']}\n";

    if ($results['total_results'] > 0) {
        foreach ($results['available_room_types'] as $room) {
            echo "\n  {$room['room_type']['name']}:\n";
            echo "    Available Rooms: {$room['availability']['available_rooms']}\n";
            echo "    Total Price: \${$room['pricing']['breakdown']['total']}\n";
            echo "    Average Nightly: \${$room['pricing']['nightly_rate']['average']}\n";
            
            if (!empty($room['pricing']['discounts']['applied'])) {
                echo "    Discounts Applied:\n";
                foreach ($room['pricing']['discounts']['applied'] as $discount) {
                    echo "      - {$discount['description']}: \${$discount['amount']}\n";
                }
            }
        }
    } else {
        echo "\n  No rooms available\n";
        echo "\n  Possible issues:\n";
        echo "  1. Check if inventory exists for the dates\n";
        echo "  2. Check if rooms are fully booked\n";
        echo "  3. Check if adult count exceeds max occupancy\n";
    }
} catch (\Exception $e) {
    echo "❌ Error during search: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n✅ Diagnostic complete!\n";

// Helper function
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