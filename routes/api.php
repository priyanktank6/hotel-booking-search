<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/search', [SearchController::class, 'search'])->name('api.search');

// Simple health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
        'time' => now()->toDateTimeString()
    ]);
});

// Database health check
Route::get('/db-health', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 'healthy',
            'database' => 'connected'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Add this route to check database tables
Route::get('/check-tables', function () {
    try {
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        $tableNames = array_column($tables, 'tablename');
        
        $requiredTables = ['room_types', 'rooms', 'inventory', 'discounts', 'rate_plans', 'rate_plan_discounts'];
        $existingTables = array_intersect($requiredTables, $tableNames);
        $missingTables = array_diff($requiredTables, $tableNames);
        
        return response()->json([
            'status' => 'ok',
            'existing_tables' => $existingTables,
            'missing_tables' => $missingTables,
            'all_tables_exist' => empty($missingTables),
            'total_tables' => count($tableNames),
            'table_list' => $tableNames
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/setup-database', function () {
    try {
        // Check if tables exist
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        $tableNames = array_column($tables, 'tablename');
        
        $created = [];
        
        // Create room_types table if not exists (Updated with min_occupancy)
        if (!in_array('room_types', $tableNames)) {
            DB::statement('
                CREATE TABLE room_types (
                    id BIGSERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE NOT NULL,
                    description TEXT,
                    max_occupancy INTEGER DEFAULT 3,
                    min_occupancy INTEGER DEFAULT 1,
                    base_price INTEGER NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ');
            $created[] = 'room_types';
            
            // Insert sample data with correct occupancy (Standard: 3, Deluxe: 4)
            DB::table('room_types')->insert([
                [
                    'name' => 'Standard Room', 
                    'slug' => 'standard', 
                    'description' => 'Comfortable standard room with essential amenities', 
                    'max_occupancy' => 3, 
                    'min_occupancy' => 1,
                    'base_price' => 100, 
                    'created_at' => now(), 
                    'updated_at' => now()
                ],
                [
                    'name' => 'Deluxe Room', 
                    'slug' => 'deluxe', 
                    'description' => 'Spacious deluxe room with premium amenities', 
                    'max_occupancy' => 4, 
                    'min_occupancy' => 1,
                    'base_price' => 150, 
                    'created_at' => now(), 
                    'updated_at' => now()
                ],
            ]);
        }
        
        // Create rooms table if not exists
        if (!in_array('rooms', $tableNames)) {
            DB::statement('
                CREATE TABLE rooms (
                    id BIGSERIAL PRIMARY KEY,
                    room_type_id BIGINT NOT NULL REFERENCES room_types(id) ON DELETE CASCADE,
                    room_number VARCHAR(255) UNIQUE NOT NULL,
                    is_active BOOLEAN DEFAULT true,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ');
            $created[] = 'rooms';
            
            // Insert rooms (5 each)
            for ($i = 1; $i <= 5; $i++) {
                DB::table('rooms')->insert([
                    'room_type_id' => 1,
                    'room_number' => 'STD-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                DB::table('rooms')->insert([
                    'room_type_id' => 2,
                    'room_number' => 'DLX-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        
        // Create rate_plans table if not exists (NEW for Round 2)
        if (!in_array('rate_plans', $tableNames)) {
            DB::statement('
                CREATE TABLE rate_plans (
                    id BIGSERIAL PRIMARY KEY,
                    room_type_id BIGINT NOT NULL REFERENCES room_types(id) ON DELETE CASCADE,
                    code VARCHAR(50) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    meal_charge_per_night DECIMAL(10,2) DEFAULT 0,
                    is_active BOOLEAN DEFAULT true,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(room_type_id, code)
                )
            ');
            $created[] = 'rate_plans';
            
            // Insert rate plans
            DB::table('rate_plans')->insert([
                // Standard room rate plans
                ['room_type_id' => 1, 'code' => 'EP', 'name' => 'Room Only', 'description' => 'Room only, no meals included', 'meal_charge_per_night' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['room_type_id' => 1, 'code' => 'CP', 'name' => 'Breakfast Included', 'description' => 'Includes breakfast', 'meal_charge_per_night' => 25, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                // Deluxe room rate plans
                ['room_type_id' => 2, 'code' => 'CP', 'name' => 'Breakfast Included', 'description' => 'Includes breakfast', 'meal_charge_per_night' => 30, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['room_type_id' => 2, 'code' => 'MAP', 'name' => 'All Meals Included', 'description' => 'Includes breakfast, lunch, and dinner', 'meal_charge_per_night' => 75, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
        
        // Create rate_plan_discounts table if not exists (NEW for Round 2)
        if (!in_array('rate_plan_discounts', $tableNames)) {
            DB::statement('
                CREATE TABLE rate_plan_discounts (
                    id BIGSERIAL PRIMARY KEY,
                    rate_plan_id BIGINT NOT NULL REFERENCES rate_plans(id) ON DELETE CASCADE,
                    discount_type VARCHAR(50) NOT NULL,
                    min_nights INTEGER,
                    max_nights INTEGER,
                    days_before_checkin INTEGER,
                    discount_percentage DECIMAL(5,2) NOT NULL,
                    is_active BOOLEAN DEFAULT true,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ');
            $created[] = 'rate_plan_discounts';
            
            // Get rate plan IDs
            $epStandard = DB::table('rate_plans')->where('code', 'EP')->where('room_type_id', 1)->first();
            $cpStandard = DB::table('rate_plans')->where('code', 'CP')->where('room_type_id', 1)->first();
            $cpDeluxe = DB::table('rate_plans')->where('code', 'CP')->where('room_type_id', 2)->first();
            $mapDeluxe = DB::table('rate_plans')->where('code', 'MAP')->where('room_type_id', 2)->first();
            
            // Insert early bird discounts
            if ($epStandard) {
                DB::table('rate_plan_discounts')->insert([
                    'rate_plan_id' => $epStandard->id,
                    'discount_type' => 'early_bird',
                    'days_before_checkin' => 7,
                    'discount_percentage' => 5,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            foreach ([$cpStandard, $cpDeluxe, $mapDeluxe] as $ratePlan) {
                if ($ratePlan) {
                    DB::table('rate_plan_discounts')->insert([
                        'rate_plan_id' => $ratePlan->id,
                        'discount_type' => 'early_bird',
                        'days_before_checkin' => 7,
                        'discount_percentage' => 10,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            
            // Insert long stay discounts for all rate plans
            $allRatePlans = DB::table('rate_plans')->get();
            foreach ($allRatePlans as $ratePlan) {
                DB::table('rate_plan_discounts')->insert([
                    'rate_plan_id' => $ratePlan->id,
                    'discount_type' => 'long_stay',
                    'min_nights' => 3,
                    'max_nights' => 6,
                    'discount_percentage' => 10,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                DB::table('rate_plan_discounts')->insert([
                    'rate_plan_id' => $ratePlan->id,
                    'discount_type' => 'long_stay',
                    'min_nights' => 7,
                    'max_nights' => null,
                    'discount_percentage' => 15,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                DB::table('rate_plan_discounts')->insert([
                    'rate_plan_id' => $ratePlan->id,
                    'discount_type' => 'last_minute',
                    'days_before_checkin' => 3,
                    'discount_percentage' => 20,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        
        // Add rate_plan_id to inventory if not exists
        try {
            DB::statement('ALTER TABLE inventory ADD COLUMN IF NOT EXISTS rate_plan_id BIGINT REFERENCES rate_plans(id)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        
        // Create inventory table if not exists (Updated with rate_plan_id)
        if (!in_array('inventory', $tableNames)) {
            DB::statement('
                CREATE TABLE inventory (
                    id BIGSERIAL PRIMARY KEY,
                    room_type_id BIGINT NOT NULL REFERENCES room_types(id) ON DELETE CASCADE,
                    rate_plan_id BIGINT REFERENCES rate_plans(id),
                    date DATE NOT NULL,
                    total_rooms INTEGER DEFAULT 5,
                    booked_rooms INTEGER DEFAULT 0,
                    price INTEGER NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(room_type_id, rate_plan_id, date)
                )
            ');
            $created[] = 'inventory';
            
            // Insert inventory for all rate plans
            $ratePlans = DB::table('rate_plans')->get();
            for ($i = 0; $i <= 30; $i++) {
                $date = now()->addDays($i)->format('Y-m-d');
                foreach ($ratePlans as $ratePlan) {
                    $basePrice = $ratePlan->room_type_id == 1 ? 100 : 150;
                    DB::table('inventory')->insert([
                        'room_type_id' => $ratePlan->room_type_id,
                        'rate_plan_id' => $ratePlan->id,
                        'date' => $date,
                        'total_rooms' => 5,
                        'booked_rooms' => rand(0, 2),
                        'price' => $basePrice,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        } else {
            // Update existing inventory with rate_plan_id if missing
            $standardEP = DB::table('rate_plans')->where('room_type_id', 1)->where('code', 'EP')->first();
            $standardCP = DB::table('rate_plans')->where('room_type_id', 1)->where('code', 'CP')->first();
            $deluxeCP = DB::table('rate_plans')->where('room_type_id', 2)->where('code', 'CP')->first();
            $deluxeMAP = DB::table('rate_plans')->where('room_type_id', 2)->where('code', 'MAP')->first();
            
            if ($standardEP) {
                DB::table('inventory')->where('room_type_id', 1)->whereNull('rate_plan_id')->update(['rate_plan_id' => $standardEP->id]);
            }
        }
        
        // Create discounts table if not exists (General discounts - kept for backward compatibility)
        if (!in_array('discounts', $tableNames)) {
            DB::statement('
                CREATE TABLE discounts (
                    id BIGSERIAL PRIMARY KEY,
                    room_type_id BIGINT NOT NULL REFERENCES room_types(id) ON DELETE CASCADE,
                    type VARCHAR(50) NOT NULL,
                    min_nights INTEGER,
                    max_nights INTEGER,
                    days_before_checkin INTEGER,
                    discount_percentage DECIMAL(5,2) NOT NULL,
                    is_active BOOLEAN DEFAULT true,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ');
            $created[] = 'discounts';
            
            // Insert general discounts (long stay, last minute)
            DB::table('discounts')->insert([
                // Standard room long stay
                ['room_type_id' => 1, 'type' => 'long_stay', 'min_nights' => 3, 'max_nights' => 6, 'discount_percentage' => 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['room_type_id' => 1, 'type' => 'long_stay', 'min_nights' => 7, 'max_nights' => null, 'discount_percentage' => 15, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                // Deluxe room long stay
                ['room_type_id' => 2, 'type' => 'long_stay', 'min_nights' => 3, 'max_nights' => 6, 'discount_percentage' => 12, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['room_type_id' => 2, 'type' => 'long_stay', 'min_nights' => 7, 'max_nights' => null, 'discount_percentage' => 18, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                // Last minute discounts
                ['room_type_id' => 1, 'type' => 'last_minute', 'days_before_checkin' => 3, 'discount_percentage' => 20, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['room_type_id' => 2, 'type' => 'last_minute', 'days_before_checkin' => 3, 'discount_percentage' => 25, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'tables_created' => $created,
            'message' => 'Database setup completed successfully for Round 2',
            'all_tables_exist' => true
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'line' => $e->getLine()
        ], 500);
    }
});

// Add this at the end of routes/api.php
Route::get('/test-availability', function () {
    try {
        // Test database connection
        DB::connection()->getPdo();
        
        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'message' => 'Application is running correctly',
            'timestamp' => now()->toISOString(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'database' => 'disconnected',
            'error' => $e->getMessage(),
        ], 500);
    }
});
