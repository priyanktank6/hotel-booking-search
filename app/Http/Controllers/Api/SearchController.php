<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchAvailabilityRequest;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;

class SearchController extends Controller
{
    protected RoomAvailabilityService $availabilityService;
    
    public function __construct(RoomAvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }
    
    /**
     * Search for available rooms
     */
    public function search(SearchAvailabilityRequest $request)
    {
        try {
            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);
            $adults = (int) $request->adults;
            $mealPlan = $request->meal_plan;
            
            // Validate that check-out is after check-in
            if ($checkOut->lte($checkIn)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-out date must be after check-in date',
                ], 422);
            }
            
            // Get available rooms
            $results = $this->availabilityService->getAvailableRooms(
                $checkIn,
                $checkOut,
                $adults,
                $mealPlan
            );
            
            return response()->json([
                'success' => true,
                'message' => $results['total_results'] > 0 
                    ? 'Rooms found successfully' 
                    : 'No rooms available for the selected criteria',
                'data' => $results,
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Search error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching for rooms',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}