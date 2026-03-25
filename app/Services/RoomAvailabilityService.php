<?php

namespace App\Services;

use App\Models\RoomType;
use App\Models\Inventory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RoomAvailabilityService
{
    protected PricingCalculator $pricingCalculator;
    
    public function __construct(PricingCalculator $pricingCalculator)
    {
        $this->pricingCalculator = $pricingCalculator;
    }
    
    /**
     * Get available rooms for given dates and criteria
     */
    public function getAvailableRooms(Carbon $checkIn, Carbon $checkOut, int $adults, string $mealPlan): array
    {
        $nights = $checkIn->diffInDays($checkOut);
        $dates = $this->getDateRange($checkIn, $checkOut);
        
        // Get all room types
        $roomTypes = RoomType::with('discounts')->get();
        
        if ($roomTypes->isEmpty()) {
            return $this->formatResponse($checkIn, $checkOut, $nights, $adults, $mealPlan, []);
        }
        
        $availableRooms = [];
        
        foreach ($roomTypes as $roomType) {
            // Check if room can accommodate the number of adults
            if ($roomType->max_occupancy < $adults) {
                continue;
            }
            
            // Check availability for the date range
            $availabilityData = $this->checkRoomTypeAvailability($roomType, $dates);
            
            if ($availabilityData['available']) {
                // Calculate pricing
                $pricing = $this->pricingCalculator->calculate(
                    $roomType,
                    $availabilityData['daily_prices'],
                    $nights,
                    $checkIn,
                    $mealPlan
                );
                
                $availableRooms[] = [
                    'room_type' => [
                        'id' => $roomType->id,
                        'name' => $roomType->name,
                        'slug' => $roomType->slug,
                        'description' => $roomType->description,
                        'max_occupancy' => $roomType->max_occupancy,
                    ],
                    'availability' => [
                        'available_rooms' => $availabilityData['max_rooms_available'],
                        'daily_breakdown' => $availabilityData['daily_breakdown'],
                    ],
                    'pricing' => $pricing,
                    'stay_details' => [
                        'nights' => $nights,
                        'adults' => $adults,
                        'meal_plan' => $mealPlan,
                    ],
                ];
            }
        }
        
        return $this->formatResponse(
            $checkIn, 
            $checkOut, 
            $nights, 
            $adults, 
            $mealPlan, 
            $availableRooms
        );
    }
    
    /**
     * Check availability for a specific room type across date range
     */
    private function checkRoomTypeAvailability(RoomType $roomType, array $dates): array
    {
        $dateStrings = array_map(fn($date) => $date->format('Y-m-d'), $dates);
        
        // Get inventory for all requested dates
        $inventory = Inventory::where('room_type_id', $roomType->id)
            ->whereIn('date', $dateStrings)
            ->get();
        
        // Check if we have inventory for all dates
        if ($inventory->count() !== count($dates)) {
            $foundDates = $inventory->pluck('date')->toArray();
            $missingDates = array_diff($dateStrings, $foundDates);
            
            Log::warning("Missing inventory for {$roomType->name}", [
                'missing_dates' => $missingDates,
                'total_needed' => count($dates),
                'total_found' => $inventory->count()
            ]);
            
            return [
                'available' => false,
                'max_rooms_available' => 0,
                'daily_prices' => [],
                'daily_breakdown' => $this->createEmptyDailyBreakdown($dateStrings),
            ];
        }
        
        $dailyBreakdown = [];
        $dailyPrices = [];
        $minAvailableRooms = PHP_INT_MAX;
        $allDatesAvailable = true;
        
        // Create a map for quick lookup using string dates as keys
        $inventoryMap = [];
        foreach ($inventory as $item) {
            // Convert date to string for array key
            $dateKey = $item->date instanceof Carbon ? $item->date->format('Y-m-d') : $item->date;
            $inventoryMap[$dateKey] = $item;
        }
        
        // Check each date
        foreach ($dates as $date) {
            $dateString = $date->format('Y-m-d');
            
            // Safety check - ensure inventory exists
            if (!isset($inventoryMap[$dateString])) {
                Log::error("Inventory not found for {$roomType->name} on {$dateString}");
                return [
                    'available' => false,
                    'max_rooms_available' => 0,
                    'daily_prices' => [],
                    'daily_breakdown' => $this->createEmptyDailyBreakdown($dateStrings),
                ];
            }
            
            $inventoryItem = $inventoryMap[$dateString];
            
            // Safely calculate available rooms
            $totalRooms = $inventoryItem->total_rooms ?? 0;
            $bookedRooms = $inventoryItem->booked_rooms ?? 0;
            $availableRooms = $totalRooms - $bookedRooms;
            $price = $inventoryItem->price ?? 0;
            
            $dailyPrices[] = $price;
            
            $dailyBreakdown[$dateString] = [
                'total_rooms' => $totalRooms,
                'booked_rooms' => $bookedRooms,
                'available_rooms' => $availableRooms,
                'price' => $price,
                'is_available' => $availableRooms > 0,
            ];
            
            if ($availableRooms <= 0) {
                $allDatesAvailable = false;
                $minAvailableRooms = 0;
                break;
            }
            
            $minAvailableRooms = min($minAvailableRooms, $availableRooms);
        }
        
        return [
            'available' => $allDatesAvailable && $minAvailableRooms > 0,
            'max_rooms_available' => $allDatesAvailable ? $minAvailableRooms : 0,
            'daily_prices' => $dailyPrices,
            'daily_breakdown' => $dailyBreakdown,
        ];
    }
    
    /**
     * Create empty daily breakdown for missing dates
     */
    private function createEmptyDailyBreakdown(array $dateStrings): array
    {
        $breakdown = [];
        foreach ($dateStrings as $dateString) {
            $breakdown[$dateString] = [
                'total_rooms' => 0,
                'booked_rooms' => 0,
                'available_rooms' => 0,
                'price' => 0,
                'is_available' => false,
                'error' => 'No inventory data available for this date',
            ];
        }
        return $breakdown;
    }
    
    /**
     * Get date range between check-in and check-out (excluding check-out)
     */
    private function getDateRange(Carbon $checkIn, Carbon $checkOut): array
    {
        $dates = [];
        $currentDate = $checkIn->copy();
        
        while ($currentDate->lt($checkOut)) {
            $dates[] = $currentDate->copy();
            $currentDate->addDay();
        }
        
        return $dates;
    }
    
    /**
     * Format the response
     */
    private function formatResponse(
        Carbon $checkIn, 
        Carbon $checkOut, 
        int $nights, 
        int $adults, 
        string $mealPlan, 
        array $availableRooms
    ): array {
        return [
            'search_criteria' => [
                'check_in' => $checkIn->format('Y-m-d'),
                'check_out' => $checkOut->format('Y-m-d'),
                'nights' => $nights,
                'adults' => $adults,
                'meal_plan' => $mealPlan,
            ],
            'available_room_types' => $availableRooms,
            'total_results' => count($availableRooms),
            'summary' => [
                'total_room_types_checked' => \App\Models\RoomType::count(),
                'available_room_types' => count($availableRooms),
            ],
        ];
    }
}