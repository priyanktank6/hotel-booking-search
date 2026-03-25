<?php

namespace App\Services;

use App\Models\RoomType;
use Carbon\Carbon;

class PricingCalculator
{
    private const BREAKFAST_COST_PER_NIGHT = 25;
    
    /**
     * Calculate total price with all applicable discounts
     */
    public function calculate(
        RoomType $roomType, 
        array $dailyPrices, 
        int $nights, 
        Carbon $checkIn, 
        string $mealPlan
    ): array {
        $subtotal = array_sum($dailyPrices);
        
        // Get applicable discounts
        $discounts = $this->getApplicableDiscounts($roomType, $nights, $checkIn);
        
        // Calculate discount amount
        $totalDiscount = 0;
        $appliedDiscounts = [];
        
        foreach ($discounts as $discount) {
            $discountAmount = ($subtotal * $discount->discount_percentage) / 100;
            $totalDiscount += $discountAmount;
            $appliedDiscounts[] = [
                'type' => $discount->type === 'long_stay' ? 'Long Stay Discount' : 'Last Minute Discount',
                'percentage' => $discount->discount_percentage,
                'amount' => round($discountAmount, 2),
                'description' => $discount->type === 'long_stay' 
                    ? "{$discount->discount_percentage}% off for staying {$nights} nights"
                    : "{$discount->discount_percentage}% off for last minute booking",
            ];
        }
        
        $roomTotal = $subtotal - $totalDiscount;
        
        // Add meal plan charges
        $mealPlanDetails = $this->calculateMealPlanCharges($mealPlan, $nights);
        $totalPrice = $roomTotal + $mealPlanDetails['charge'];
        
        return [
            'breakdown' => [
                'subtotal' => round($subtotal, 2),
                'discount' => round($totalDiscount, 2),
                'meal_plan_charge' => round($mealPlanDetails['charge'], 2),
                'total' => round($totalPrice, 2),
            ],
            'nightly_rate' => [
                'average' => round($totalPrice / $nights, 2),
                'daily_rates' => array_map(fn($price) => round($price, 2), $dailyPrices),
            ],
            'discounts' => [
                'applied' => $appliedDiscounts,
                'total_saved' => round($totalDiscount, 2),
            ],
            'meal_plan' => $mealPlanDetails,
            'nights' => $nights,
        ];
    }
    
    /**
     * Get applicable discounts for the stay
     */
    private function getApplicableDiscounts(RoomType $roomType, int $nights, Carbon $checkIn): array
    {
        $discounts = [];
        
        // Check for long stay discount
        $longStayDiscount = $roomType->discounts()
            ->where('type', 'long_stay')
            ->where('is_active', true)
            ->where('min_nights', '<=', $nights)
            ->where(function($query) use ($nights) {
                $query->whereNull('max_nights')
                      ->orWhere('max_nights', '>=', $nights);
            })
            ->first();
            
        if ($longStayDiscount) {
            $discounts[] = $longStayDiscount;
        }
        
        // Check for last minute discount (within 3 days of check-in)
        $daysUntilCheckIn = Carbon::now()->diffInDays($checkIn);
        $lastMinuteDiscount = $roomType->discounts()
            ->where('type', 'last_minute')
            ->where('is_active', true)
            ->where('days_before_checkin', '>=', $daysUntilCheckIn)
            ->first();
            
        if ($lastMinuteDiscount) {
            $discounts[] = $lastMinuteDiscount;
        }
        
        return $discounts;
    }
    
    /**
     * Calculate meal plan charges
     */
    private function calculateMealPlanCharges(string $mealPlan, int $nights): array
    {
        if ($mealPlan === 'breakfast_included') {
            $charge = self::BREAKFAST_COST_PER_NIGHT * $nights;
            return [
                'type' => 'Breakfast Included',
                'charge' => $charge,
                'per_night' => self::BREAKFAST_COST_PER_NIGHT,
                'description' => "Breakfast for all guests at \${$charge} total",
            ];
        }
        
        return [
            'type' => 'Room Only',
            'charge' => 0,
            'per_night' => 0,
            'description' => 'No meal plan selected',
        ];
    }
}