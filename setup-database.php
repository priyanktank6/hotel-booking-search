<?php
// setup-database.php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

echo "Setting up database tables...\n";

// Create room_types table
if (!Schema::hasTable('room_types')) {
    Schema::create('room_types', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->integer('max_occupancy')->default(3);
        $table->integer('base_price');
        $table->timestamps();
    });
    echo "✅ room_types table created\n";
}

// Create rooms table
if (!Schema::hasTable('rooms')) {
    Schema::create('rooms', function (Blueprint $table) {
        $table->id();
        $table->foreignId('room_type_id')->constrained();
        $table->string('room_number')->unique();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    echo "✅ rooms table created\n";
}

// Create inventory table
if (!Schema::hasTable('inventory')) {
    Schema::create('inventory', function (Blueprint $table) {
        $table->id();
        $table->foreignId('room_type_id')->constrained();
        $table->date('date');
        $table->integer('total_rooms')->default(5);
        $table->integer('booked_rooms')->default(0);
        $table->integer('price');
        $table->timestamps();
        $table->unique(['room_type_id', 'date']);
    });
    echo "✅ inventory table created\n";
}

// Create discounts table
if (!Schema::hasTable('discounts')) {
    Schema::create('discounts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('room_type_id')->constrained();
        $table->string('type');
        $table->integer('min_nights')->nullable();
        $table->integer('max_nights')->nullable();
        $table->integer('days_before_checkin')->nullable();
        $table->decimal('discount_percentage', 5, 2);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    echo "✅ discounts table created\n";
}

// Insert sample data
if (DB::table('room_types')->count() == 0) {
    DB::table('room_types')->insert([
        ['name' => 'Standard Room', 'slug' => 'standard', 'description' => 'Comfortable standard room', 'max_occupancy' => 3, 'base_price' => 100],
        ['name' => 'Deluxe Room', 'slug' => 'deluxe', 'description' => 'Spacious deluxe room', 'max_occupancy' => 3, 'base_price' => 150],
    ]);
    echo "✅ room types data inserted\n";
}

if (DB::table('rooms')->count() == 0) {
    for ($i = 1; $i <= 5; $i++) {
        DB::table('rooms')->insert(['room_type_id' => 1, 'room_number' => "STD-" . str_pad($i, 3, '0', STR_PAD_LEFT), 'is_active' => true]);
        DB::table('rooms')->insert(['room_type_id' => 2, 'room_number' => "DLX-" . str_pad($i, 3, '0', STR_PAD_LEFT), 'is_active' => true]);
    }
    echo "✅ rooms data inserted\n";
}

echo "Database setup complete!\n";