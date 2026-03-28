<?php
// check-availability.php - Simple Availability Checker with Error Handling
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Inventory;
use Carbon\Carbon;

echo "========================================\n";
echo "ROOM AVAILABILITY CHECKER\n";
echo "========================================\n\n";

// Get date from command line or use tomorrow
$checkDate = $argv[1] ?? Carbon::tomorrow()->format('Y-m-d');
$nights = $argv[2] ?? 3;

echo "Checking availability for: {$checkDate} (+ {$nights} nights)\n";
echo str_repeat("-", 100) . "\n\n";

// Check if inventory has rate_plan_id
$sampleInventory = Inventory::first();
if ($sampleInventory && !$sampleInventory->rate_plan_id) {
    echo "⚠️  WARNING: Inventory records don't have rate_plan_id set!\n";
    echo "   Please run: php artisan db:seed --class=InventoryTableSeeder\n\n";
}

// Check each date
for ($i = 0; $i < $nights; $i++) {
    $date = Carbon::parse($checkDate)->addDays($i)->format('Y-m-d');
    echo "📅 {$date}:\n";
    
    $inventory = Inventory::where('date', $date)
        ->with(['roomType', 'ratePlan'])
        ->get();
    
    if ($inventory->isEmpty()) {
        echo "  ❌ No inventory data for this date\n\n";
        continue;
    }
    
    printf("  %-20s %-12s %-10s %-10s %-10s %-10s\n", 
        "Room Type", "Rate Plan", "Total", "Booked", "Available", "Price");
    echo "  " . str_repeat("-", 85) . "\n";
    
    foreach ($inventory as $item) {
        $available = $item->total_rooms - $item->booked_rooms;
        $status = $available > 0 ? "✅ {$available}" : "❌ 0";
        
        // Safe rate plan code with fallback
        $ratePlanCode = $item->ratePlan ? $item->ratePlan->code : 'N/A';
        $ratePlanName = $item->ratePlan ? $item->ratePlan->name : 'No Rate Plan';
        
        printf("  %-20s %-12s %-10d %-10d %-10s $%-9d\n", 
            $item->roomType->name,
            $ratePlanCode,
            $item->total_rooms,
            $item->booked_rooms,
            $status,
            $item->price
        );
    }
    echo "\n";
}

// Check for fully booked dates
echo "\n📊 Fully Booked Rooms:\n";
echo str_repeat("-", 100) . "\n";

$fullyBooked = Inventory::whereRaw('total_rooms = booked_rooms')
    ->where('date', '>=', Carbon::today())
    ->with(['roomType', 'ratePlan'])
    ->orderBy('date')
    ->get();

if ($fullyBooked->isEmpty()) {
    echo "  No fully booked rooms found\n";
} else {
    foreach ($fullyBooked as $item) {
        $ratePlanCode = $item->ratePlan ? $item->ratePlan->code : 'N/A';
        echo "  {$item->date}: {$item->roomType->name} - {$ratePlanCode} is FULLY BOOKED\n";
    }
}

// Show summary of inventory by rate plan
echo "\n📊 Inventory Summary by Rate Plan:\n";
echo str_repeat("-", 100) . "\n";

$ratePlanStats = Inventory::where('date', '>=', Carbon::today())
    ->with('ratePlan')
    ->get()
    ->groupBy(function($item) {
        return $item->ratePlan ? $item->ratePlan->code : 'no_rate_plan';
    });

foreach ($ratePlanStats as $code => $items) {
    $totalRecords = $items->count();
    $avgPrice = $items->avg('price');
    $avgAvailable = $items->avg(function($item) {
        return $item->total_rooms - $item->booked_rooms;
    });
    
    echo "  {$code}: {$totalRecords} records, Avg Price: \${$avgPrice}, Avg Available: {$avgAvailable} rooms\n";
}

echo "\n========================================\n";