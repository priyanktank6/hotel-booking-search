<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    // return redirect('/test-search');
    return view('welcome');
});

Route::get('/test-search', function () {
    return view('test-search');
});

// Simple API info page
Route::get('/api-info', function () {
    return response()->json([
        'name' => 'Hotel Booking Search API',
        'version' => '1.0',
        'endpoints' => [
            'search' => [
                'url' => '/api/search',
                'method' => 'POST',
                'description' => 'Search for available rooms',
                'body' => [
                    'check_in' => 'YYYY-MM-DD',
                    'check_out' => 'YYYY-MM-DD',
                    'adults' => 'integer',
                    'meal_plan' => 'room_only or breakfast_included'
                ]
            ],
            'health' => [
                'url' => '/api/health',
                'method' => 'GET',
                'description' => 'Check if API is running'
            ],
            'db-health' => [
                'url' => '/api/db-health',
                'method' => 'GET',
                'description' => 'Check database connection'
            ],
            'test-interface' => [
                'url' => '/test-search',
                'method' => 'GET',
                'description' => 'Test interface for searching rooms'
            ]
        ]
    ]);
});