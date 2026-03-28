<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RatePlan extends Model
{
    use HasFactory;

    protected $fillable = ['room_type_id', 'code', 'name', 'description', 'meal_charge_per_night', 'is_active'];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(RatePlanDiscount::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
