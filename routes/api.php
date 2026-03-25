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

// Add this at the end of routes/api.php
Route::get('/test-availability', function () {
    try {
        // Test database connection
        DB::connection()->getPdo();
        
        // Get some stats
        $roomTypes = \App\Models\RoomType::count();
        $rooms = \App\Models\Room::count();
        
        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'stats' => [
                'room_types' => $roomTypes,
                'rooms' => $rooms,
            ],
            'timestamp' => now(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
        ], 500);
    }
});
