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
