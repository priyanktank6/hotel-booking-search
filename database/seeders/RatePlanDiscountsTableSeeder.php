<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RatePlanDiscount;
use App\Models\RatePlan;

class RatePlanDiscountsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Get rate plans
        $epStandard = RatePlan::where('code', 'EP')->whereHas('roomType', fn($q) => $q->where('slug', 'standard'))->first();
        $cpStandard = RatePlan::where('code', 'CP')->whereHas('roomType', fn($q) => $q->where('slug', 'standard'))->first();
        $cpDeluxe = RatePlan::where('code', 'CP')->whereHas('roomType', fn($q) => $q->where('slug', 'deluxe'))->first();
        $mapDeluxe = RatePlan::where('code', 'MAP')->whereHas('roomType', fn($q) => $q->where('slug', 'deluxe'))->first();
        
        // EP Rate Plan: 5% early bird discount
        if ($epStandard) {
            RatePlanDiscount::create([
                'rate_plan_id' => $epStandard->id,
                'discount_type' => 'early_bird',
                'min_nights' => null,
                'max_nights' => null,
                'days_before_checkin' => 7,
                'discount_percentage' => 5,
                'is_active' => true,
            ]);
        }
        
        // CP and MAP Rate Plans: 10% early bird discount
        foreach ([$cpStandard, $cpDeluxe, $mapDeluxe] as $ratePlan) {
            if ($ratePlan) {
                RatePlanDiscount::create([
                    'rate_plan_id' => $ratePlan->id,
                    'discount_type' => 'early_bird',
                    'min_nights' => null,
                    'max_nights' => null,
                    'days_before_checkin' => 7,
                    'discount_percentage' => 10,
                    'is_active' => true,
                ]);
            }
        }
        
        // Note: Long stay and last minute discounts are handled by the general DiscountsTableSeeder
        // They apply to all rate plans through the room type association
    }
}