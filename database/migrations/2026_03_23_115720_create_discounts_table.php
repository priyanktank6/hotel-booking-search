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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'long_stay' or 'last_minute'
            $table->integer('min_nights')->nullable()->comment('Minimum nights for long stay');
            $table->integer('max_nights')->nullable()->comment('Maximum nights for long stay');
            $table->integer('days_before_checkin')->nullable()->comment('Days before checkin for last minute');
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
        Schema::dropIfExists('discounts');
    }
};
