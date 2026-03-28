<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rate_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->onDelete('cascade');
            $table->string('code'); // EP, CP, MAP
            $table->string('name'); // Room Only, Breakfast Included, All Meals Included
            $table->text('description')->nullable();
            $table->decimal('meal_charge_per_night', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['room_type_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_plans');
    }
};
