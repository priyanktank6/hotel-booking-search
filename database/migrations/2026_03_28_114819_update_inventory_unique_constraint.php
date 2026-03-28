<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove old rule
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropUnique('inventory_room_type_id_date_unique');
        });
        
        // Add new rule that allows multiple rate plans per day
        Schema::table('inventory', function (Blueprint $table) {
            $table->unique(['room_type_id', 'rate_plan_id', 'date'], 'inventory_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropUnique('inventory_unique');
            $table->unique(['room_type_id', 'date'], 'inventory_room_type_id_date_unique');
        });
    }
};