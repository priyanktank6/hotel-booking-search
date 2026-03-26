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
        
        $requiredTables = ['room_types', 'rooms', 'inventory', 'discounts'];
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
        
        // Create room_types table if not exists
        if (!in_array('room_types', $tableNames)) {
            DB::statement('
                CREATE TABLE room_types (
                    id BIGSERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE NOT NULL,
                    description TEXT,
                    max_occupancy INTEGER DEFAULT 3,
                    base_price INTEGER NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ');
            $created[] = 'room_types';
            
            // Insert sample data
            DB::table('room_types')->insert([
                ['name' => 'Standard Room', 'slug' => 'standard', 'description' => 'Comfortable standard room', 'max_occupancy' => 3, 'base_price' => 100, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Deluxe Room', 'slug' => 'deluxe', 'description' => 'Spacious deluxe room', 'max_occupancy' => 3, 'base_price' => 150, 'created_at' => now(), 'updated_at' => now()],
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
            
            // Insert rooms
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
        
        // Create inventory table if not exists
        if (!in_array('inventory', $tableNames)) {
            DB::statement('
                CREATE TABLE inventory (
                    id BIGSERIAL PRIMARY KEY,
                    room_type_id BIGINT NOT NULL REFERENCES room_types(id) ON DELETE CASCADE,
                    date DATE NOT NULL,
                    total_rooms INTEGER DEFAULT 5,
                    booked_rooms INTEGER DEFAULT 0,
                    price INTEGER NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(room_type_id, date)
                )
            ');
            $created[] = 'inventory';
            
            // Insert inventory for next 30 days
            for ($i = 0; $i <= 30; $i++) {
                $date = now()->addDays($i)->format('Y-m-d');
                DB::table('inventory')->insert([
                    'room_type_id' => 1,
                    'date' => $date,
                    'total_rooms' => 5,
                    'booked_rooms' => rand(0, 2),
                    'price' => 100,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                DB::table('inventory')->insert([
                    'room_type_id' => 2,
                    'date' => $date,
                    'total_rooms' => 5,
                    'booked_rooms' => rand(0, 2),
                    'price' => 150,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        
        // Create discounts table if not exists
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
            
            // Insert discounts individually
            DB::table('discounts')->insert([
                'room_type_id' => 1,
                'type' => 'long_stay',
                'min_nights' => 3,
                'max_nights' => 6,
                'days_before_checkin' => null,
                'discount_percentage' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('discounts')->insert([
                'room_type_id' => 1,
                'type' => 'long_stay',
                'min_nights' => 7,
                'max_nights' => null,
                'days_before_checkin' => null,
                'discount_percentage' => 15,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('discounts')->insert([
                'room_type_id' => 2,
                'type' => 'long_stay',
                'min_nights' => 3,
                'max_nights' => 6,
                'days_before_checkin' => null,
                'discount_percentage' => 12,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('discounts')->insert([
                'room_type_id' => 2,
                'type' => 'long_stay',
                'min_nights' => 7,
                'max_nights' => null,
                'days_before_checkin' => null,
                'discount_percentage' => 18,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('discounts')->insert([
                'room_type_id' => 1,
                'type' => 'last_minute',
                'min_nights' => null,
                'max_nights' => null,
                'days_before_checkin' => 3,
                'discount_percentage' => 20,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('discounts')->insert([
                'room_type_id' => 2,
                'type' => 'last_minute',
                'min_nights' => null,
                'max_nights' => null,
                'days_before_checkin' => 3,
                'discount_percentage' => 25,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'tables_created' => $created,
            'message' => 'Database setup completed successfully',
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
