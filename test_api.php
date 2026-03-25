<?php
// test_api.php - Simple API test script for Windows

echo "=== Testing Hotel Booking API ===\n\n";

// API endpoint
$url = 'http://localhost:8000/api/search';

// Get tomorrow and 3 days after
$tomorrow = date('Y-m-d', strtotime('tomorrow'));
$checkout = date('Y-m-d', strtotime('tomorrow + 3 days'));

// Test data
$data = [
    'check_in' => $tomorrow,
    'check_out' => $checkout,
    'adults' => 2,
    'meal_plan' => 'room_only'
];

echo "Testing with data:\n";
print_r($data);
echo "\n";

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: " . $httpCode . "\n\n";

if ($response) {
    $result = json_decode($response, true);
    
    if ($result['success']) {
        echo "✅ Success!\n";
        echo "Message: " . $result['message'] . "\n";
        echo "Total Results: " . $result['data']['total_results'] . "\n\n";
        
        if ($result['data']['total_results'] > 0) {
            foreach ($result['data']['available_room_types'] as $room) {
                echo "Room: " . $room['room_type']['name'] . "\n";
                echo "  Available: " . $room['availability']['available_rooms'] . " rooms\n";
                echo "  Total Price: $" . $room['pricing']['breakdown']['total'] . "\n";
                echo "  Nightly Rate: $" . $room['pricing']['nightly_rate']['average'] . "\n\n";
            }
        } else {
            echo "No rooms available for these dates.\n";
        }
    } else {
        echo "❌ Error: " . $result['message'] . "\n";
    }
} else {
    echo "❌ Failed to connect to API. Make sure Laravel is running (php artisan serve)\n";
}