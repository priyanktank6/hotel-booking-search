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
        Schema::create('rate_plan_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_plan_id')->constrained()->onDelete('cascade');
            $table->string('discount_type'); // early_bird, long_stay, last_minute
            $table->integer('min_nights')->nullable();
            $table->integer('max_nights')->nullable();
            $table->integer('days_before_checkin')->nullable();
            $table->decimal('discount_percentage', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_plan_discounts');
    }
};
