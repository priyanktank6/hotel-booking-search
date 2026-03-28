<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'max_occupancy', 'min_occupancy', 'base_price'];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function ratePlans(): HasMany
    {
        return $this->hasMany(RatePlan::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }
}
