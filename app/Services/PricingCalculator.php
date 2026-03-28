<?php

namespace App\Services;

use App\Models\RoomType;
use App\Models\RatePlan;
use Carbon\Carbon;

class PricingCalculator
{
    /**
     * Calculate total price with rate plan and all applicable discounts
     */
    public function calculate(
        RoomType $roomType, 
        RatePlan $ratePlan, 
        array $dailyPrices, 
        int $nights, 
        Carbon $checkIn
    ): array {
        $roomSubtotal = array_sum($dailyPrices);
        
        // Get applicable discounts for this rate plan
        $discounts = $this->getApplicableDiscounts($ratePlan, $nights, $checkIn);
        
        // Calculate discount amount
        $totalDiscount = 0;
        $appliedDiscounts = [];
        
        foreach ($discounts as $discount) {
            $discountAmount = ($roomSubtotal * $discount->discount_percentage) / 100;
            $totalDiscount += $discountAmount;
            $appliedDiscounts[] = [
                'type' => $this->getDiscountTypeName($discount->discount_type),
                'percentage' => $discount->discount_percentage,
                'amount' => round($discountAmount, 2),
                'description' => $this->getDiscountDescription($discount, $nights),
            ];
        }
        
        $roomTotal = $roomSubtotal - $totalDiscount;
        
        // Add meal plan charges
        $mealCharge = $ratePlan->meal_charge_per_night * $nights;
        $totalPrice = $roomTotal + $mealCharge;
        
        return [
            'breakdown' => [
                'room_subtotal' => round($roomSubtotal, 2),
                'meal_plan_charge' => round($mealCharge, 2),
                'discount' => round($totalDiscount, 2),
                'total' => round($totalPrice, 2),
            ],
            'nightly_rate' => [
                'average' => round($totalPrice / $nights, 2),
                'room_only_average' => round($roomTotal / $nights, 2),
                'daily_rates' => array_map(fn($price) => round($price, 2), $dailyPrices),
            ],
            'rate_plan' => [
                'code' => $ratePlan->code,
                'name' => $ratePlan->name,
                'meal_charge_per_night' => $ratePlan->meal_charge_per_night,
            ],
            'discounts' => [
                'applied' => $appliedDiscounts,
                'total_saved' => round($totalDiscount, 2),
            ],
            'nights' => $nights,
        ];
    }
    
    /**
     * Get applicable discounts for the rate plan
     */
    private function getApplicableDiscounts(RatePlan $ratePlan, int $nights, Carbon $checkIn): array
    {
        $discounts = [];
        
        // 1. Get general discounts from room type level
        $generalDiscounts = $ratePlan->roomType->discounts()
            ->where('is_active', true)
            ->get();
        
        // 2. Get rate-plan-specific discounts
        $ratePlanDiscounts = $ratePlan->discounts()
            ->where('is_active', true)
            ->get();
        
        // Combine and check applicability
        $allDiscounts = $generalDiscounts->concat($ratePlanDiscounts);
        
        foreach ($allDiscounts as $discount) {
            $applicable = false;
            
            switch ($discount->discount_type) {
                case 'early_bird':
                    $daysUntilCheckIn = Carbon::now()->diffInDays($checkIn);
                    $applicable = $daysUntilCheckIn >= ($discount->days_before_checkin ?? 0);
                    break;
                    
                case 'long_stay':
                    $applicable = $nights >= ($discount->min_nights ?? 0);
                    if ($applicable && $discount->max_nights) {
                        $applicable = $nights <= $discount->max_nights;
                    }
                    break;
                    
                case 'last_minute':
                    $daysUntilCheckIn = Carbon::now()->diffInDays($checkIn);
                    $applicable = $daysUntilCheckIn <= ($discount->days_before_checkin ?? 0);
                    break;
            }
            
            if ($applicable) {
                $discounts[] = $discount;
            }
        }
        
        return $discounts;
    }
    
    private function getDiscountTypeName(string $type): string
    {
        return match($type) {
            'early_bird' => 'Early Bird Discount',
            'long_stay' => 'Long Stay Discount',
            'last_minute' => 'Last Minute Discount',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
    
    private function getDiscountDescription($discount, int $nights): string
    {
        return match($discount->discount_type) {
            'early_bird' => "{$discount->discount_percentage}% off for booking {$discount->days_before_checkin}+ days in advance",
            'long_stay' => "{$discount->discount_percentage}% off for staying {$nights} nights",
            'last_minute' => "{$discount->discount_percentage}% off for last minute booking",
            default => "{$discount->discount_percentage}% off",
        };
    }
}