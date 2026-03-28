<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class RatePlanDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate_plan_id', 'discount_type', 'min_nights', 'max_nights', 
        'days_before_checkin', 'discount_percentage', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }
}
