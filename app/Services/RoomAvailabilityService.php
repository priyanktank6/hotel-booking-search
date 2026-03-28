<?php

namespace App\Services;

use App\Models\RoomType;
use App\Models\RatePlan;
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
    public function getAvailableRooms(Carbon $checkIn, Carbon $checkOut, int $adults, ?string $ratePlanCode = null): array
    {
        $nights = $checkIn->diffInDays($checkOut);
        $dates = $this->getDateRange($checkIn, $checkOut);
        
        // Get all active room types
        $roomTypes = RoomType::with(['ratePlans' => function($query) {
            $query->where('is_active', true);
        }, 'ratePlans.discounts'])->get();
        
        if ($roomTypes->isEmpty()) {
            return $this->formatResponse($checkIn, $checkOut, $nights, $adults, []);
        }
        
        $availableRooms = [];
        
        foreach ($roomTypes as $roomType) {
            // Check if room can accommodate the number of adults
            if ($roomType->max_occupancy < $adults) {
                \Log::info("Room type {$roomType->name} cannot accommodate {$adults} adults");
                continue;
            }
            
            // Get applicable rate plans for this room type
            $ratePlans = $roomType->ratePlans;

            \Log::info("Checking room type: {$roomType->name}, Rate plans found: " . $ratePlans->count());
            
            // If specific rate plan requested, filter
            if ($ratePlanCode) {
                $ratePlans = $ratePlans->where('code', $ratePlanCode);
                \Log::info("Filtered by rate plan code: {$ratePlanCode}, Found: " . $ratePlans->count());
            }
            
            foreach ($ratePlans as $ratePlan) {
                \Log::info("Checking rate plan: {$ratePlan->code} for {$roomType->name}");
                // Check availability for the date range with this rate plan
                $availabilityData = $this->checkRoomTypeAvailability($roomType, $ratePlan, $dates);
                
                if ($availabilityData['available']) {
                    // Calculate pricing with rate plan and discounts
                    $pricing = $this->pricingCalculator->calculate(
                        $roomType,
                        $ratePlan,
                        $availabilityData['daily_prices'],
                        $nights,
                        $checkIn
                    );
                    
                    $availableRooms[] = [
                        'room_type' => [
                            'id' => $roomType->id,
                            'name' => $roomType->name,
                            'slug' => $roomType->slug,
                            'description' => $roomType->description,
                            'max_occupancy' => $roomType->max_occupancy,
                            'min_occupancy' => $roomType->min_occupancy,
                        ],
                        'rate_plan' => [
                            'id' => $ratePlan->id,
                            'code' => $ratePlan->code,
                            'name' => $ratePlan->name,
                            'description' => $ratePlan->description,
                            'meal_charge_per_night' => $ratePlan->meal_charge_per_night,
                        ],
                        'availability' => [
                            'available_rooms' => $availabilityData['max_rooms_available'],
                            'daily_breakdown' => $availabilityData['daily_breakdown'],
                        ],
                        'pricing' => $pricing,
                        'stay_details' => [
                            'nights' => $nights,
                            'adults' => $adults,
                        ],
                    ];
                }
            }
        }
        
        return $this->formatResponse(
            $checkIn, 
            $checkOut, 
            $nights, 
            $adults, 
            $availableRooms
        );
    }
    
    /**
     * Check availability for a specific room type and rate plan across date range
     */
    private function checkRoomTypeAvailability(RoomType $roomType, RatePlan $ratePlan, array $dates): array
    {
        $dateStrings = array_map(fn($date) => $date->format('Y-m-d'), $dates);
        
        Log::info('Checking availability', [
            'room_type' => $roomType->name,
            'rate_plan' => $ratePlan->code,
            'dates' => $dateStrings,
            'rate_plan_id' => $ratePlan->id
        ]);
        
        // Get inventory for all requested dates with this rate plan
        $inventory = Inventory::where('room_type_id', $roomType->id)
            ->where('rate_plan_id', $ratePlan->id)
            ->whereIn('date', $dateStrings)
            ->get();
        
        Log::info('Inventory found', [
            'count' => $inventory->count(),
            'dates_found' => $inventory->pluck('date')->toArray()
        ]);
        
        // Check if we have inventory for all dates
        if ($inventory->count() !== count($dates)) {
            $foundDates = $inventory->pluck('date')->toArray();
            $missingDates = array_diff($dateStrings, $foundDates);
            
            Log::warning("Missing inventory for {$roomType->name} - {$ratePlan->name}", [
                'missing_dates' => $missingDates,
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
        
        // Create a map with Y-m-d format as key for easy lookup
        $inventoryMap = [];
        foreach ($inventory as $item) {
            // Convert the date to Y-m-d format for the key
            if ($item->date instanceof Carbon) {
                $dateKey = $item->date->format('Y-m-d');
            } else {
                $dateKey = date('Y-m-d', strtotime($item->date));
            }
            $inventoryMap[$dateKey] = $item;
        }
        
        // Check each date
        foreach ($dates as $date) {
            $dateString = $date->format('Y-m-d');
            $inventoryItem = $inventoryMap[$dateString] ?? null;
            
            if (!$inventoryItem) {
                Log::error("Inventory item not found for {$roomType->name} - {$ratePlan->name} on {$dateString}");
                return [
                    'available' => false,
                    'max_rooms_available' => 0,
                    'daily_prices' => [],
                    'daily_breakdown' => $this->createEmptyDailyBreakdown($dateStrings),
                ];
            }
            
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
        array $availableRooms
    ): array {
        return [
            'search_criteria' => [
                'check_in' => $checkIn->format('Y-m-d'),
                'check_out' => $checkOut->format('Y-m-d'),
                'nights' => $nights,
                'adults' => $adults,
            ],
            'available_options' => $availableRooms,
            'total_results' => count($availableRooms),
        ];
    }
}