<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';
    
    protected $fillable = ['room_type_id', 'rate_plan_id', 'date', 'total_rooms', 'booked_rooms', 'price'];

    protected $casts = [
        'date' => 'date',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function getAvailableRoomsAttribute(): int
    {
        return $this->total_rooms - $this->booked_rooms;
    }
}
