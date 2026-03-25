<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hotel Booking Search - Test Interface</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .search-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            width: 100%;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 20px;
            margin-top: 20px;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .results {
            display: none;
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-top: 20px;
        }
        
        .room-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .room-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .room-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .room-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .price-section {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .price {
            font-size: 28px;
            font-weight: bold;
            color: #28a745;
        }
        
        .price-label {
            font-size: 14px;
            color: #666;
            margin-left: 5px;
        }
        
        .discount {
            color: #dc3545;
            font-weight: bold;
            background: #fff5f5;
            padding: 8px 12px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .meal-plan {
            background: #e3f2fd;
            padding: 8px 12px;
            border-radius: 8px;
            margin: 10px 0;
            color: #1976d2;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .info-message {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #17a2b8;
        }
        
        .row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        hr {
            margin: 20px 0;
            border: none;
            border-top: 2px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="search-card">
            <h1>🏨 Hotel Booking Search</h1>
            <p class="subtitle">Find the best rooms at the best prices</p>
            
            <form id="searchForm">
                <div class="row">
                    <div class="form-group">
                        <label>📅 Check-in Date</label>
                        <input type="date" id="check_in" required>
                    </div>
                    
                    <div class="form-group">
                        <label>📅 Check-out Date</label>
                        <input type="date" id="check_out" required>
                    </div>
                    
                    <div class="form-group">
                        <label>👥 Number of Adults</label>
                        <input type="number" id="adults" min="1" max="6" value="2" required>
                    </div>
                    
                    <div class="form-group">
                        <label>🍽️ Meal Plan</label>
                        <select id="meal_plan">
                            <option value="room_only">Room Only</option>
                            <option value="breakfast_included">Breakfast Included</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit">🔍 Search Available Rooms</button>
            </form>
        </div>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Searching for available rooms...</p>
        </div>
        
        <div id="results" class="results"></div>
    </div>

    <script>
        // Set default dates (tomorrow and 3 days after)
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        const checkout = new Date(tomorrow);
        checkout.setDate(checkout.getDate() + 3);
        
        document.getElementById('check_in').value = tomorrow.toISOString().split('T')[0];
        document.getElementById('check_out').value = checkout.toISOString().split('T')[0];
        
        // Handle form submission
        document.getElementById('searchForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const checkIn = document.getElementById('check_in').value;
            const checkOut = document.getElementById('check_out').value;
            const adults = document.getElementById('adults').value;
            const mealPlan = document.getElementById('meal_plan').value;
            
            // Validate dates
            if (!checkIn || !checkOut) {
                alert('Please select both check-in and check-out dates');
                return;
            }
            
            if (new Date(checkOut) <= new Date(checkIn)) {
                alert('Check-out date must be after check-in date');
                return;
            }
            
            const loading = document.getElementById('loading');
            const resultsDiv = document.getElementById('results');
            
            loading.style.display = 'block';
            resultsDiv.style.display = 'none';
            
            try {
                const response = await fetch('/api/search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        check_in: checkIn,
                        check_out: checkOut,
                        adults: parseInt(adults),
                        meal_plan: mealPlan
                    })
                });
                
                const data = await response.json();
                
                loading.style.display = 'none';
                resultsDiv.style.display = 'block';
                
                if (data.success) {
                    displayResults(data.data);
                } else {
                    resultsDiv.innerHTML = `<div class="error-message">❌ Error: ${data.message || 'Something went wrong'}</div>`;
                }
            } catch (error) {
                loading.style.display = 'none';
                resultsDiv.style.display = 'block';
                resultsDiv.innerHTML = `<div class="error-message">❌ Connection Error: ${error.message}<br><br>Make sure Laravel is running with: php artisan serve</div>`;
                console.error('Error:', error);
            }
        });
        
        function displayResults(data) {
            const resultsDiv = document.getElementById('results');
            
            // Check if data exists
            if (!data) {
                resultsDiv.innerHTML = '<div class="error-message">No data received from server</div>';
                return;
            }
            
            // Check if search criteria exists
            if (!data.search_criteria) {
                resultsDiv.innerHTML = '<div class="error-message">Invalid response format from server</div>';
                return;
            }
            
            // Check total results
            if (data.total_results === 0 || !data.available_room_types || data.available_room_types.length === 0) {
                resultsDiv.innerHTML = `
                    <div class="info-message">
                        <strong>ℹ️ No rooms available</strong><br><br>
                        <strong>Search Criteria:</strong><br>
                        Check-in: ${data.search_criteria.check_in}<br>
                        Check-out: ${data.search_criteria.check_out}<br>
                        Nights: ${data.search_criteria.nights}<br>
                        Adults: ${data.search_criteria.adults}<br>
                        Meal Plan: ${data.search_criteria.meal_plan === 'room_only' ? 'Room Only' : 'Breakfast Included'}<br><br>
                        <strong>Possible reasons:</strong><br>
                        • All rooms are booked for these dates<br>
                        • Inventory not available for these dates<br>
                        • Maximum occupancy exceeded<br>
                        • Try different dates
                    </div>
                `;
                return;
            }
            
            // Display search criteria
            let html = `
                <div class="info-message">
                    <strong>✅ Search Results</strong><br>
                    Found ${data.total_results} room type(s) for your stay
                </div>
                
                <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                    <strong>📋 Search Summary:</strong><br>
                    Check-in: ${data.search_criteria.check_in}<br>
                    Check-out: ${data.search_criteria.check_out}<br>
                    Nights: ${data.search_criteria.nights}<br>
                    Adults: ${data.search_criteria.adults}<br>
                    Meal Plan: ${data.search_criteria.meal_plan === 'room_only' ? 'Room Only' : 'Breakfast Included'}
                </div>
            `;
            
            // Display each room type
            data.available_room_types.forEach((room, index) => {
                // Check if pricing exists
                const pricing = room.pricing || {};
                const breakdown = pricing.breakdown || {};
                const nightlyRate = pricing.nightly_rate || {};
                const discounts = pricing.discounts || {};
                const appliedDiscounts = discounts.applied || [];
                
                html += `
                    <div class="room-card">
                        <div class="room-title">
                            ${room.room_type.name}
                            <span class="badge badge-success">${room.availability.available_rooms} rooms available</span>
                        </div>
                        <div class="room-description">${room.room_type.description || 'No description available'}</div>
                        
                        <div class="price-section">
                            <div>
                                <span class="price">$${breakdown.total || 0}</span>
                                <span class="price-label">total for ${room.stay_details.nights} nights</span>
                            </div>
                            <div style="margin-top: 10px; color: #666;">
                                Average nightly rate: $${nightlyRate.average || 0}
                            </div>
                        </div>
                `;
                
                // Show discounts if any
                if (appliedDiscounts.length > 0) {
                    html += `<div class="discount">🎉 Discounts Applied:`;
                    appliedDiscounts.forEach(discount => {
                        html += `<br>• ${discount.description}: -$${discount.amount}`;
                    });
                    html += `<br><strong>Total Saved: $${discounts.total_saved || 0}</strong></div>`;
                }
                
                // Show meal plan info
                const mealPlanInfo = pricing.meal_plan || {};
                if (mealPlanInfo.charge > 0) {
                    html += `<div class="meal-plan">🍽️ ${mealPlanInfo.type}: +$${mealPlanInfo.charge} ($${mealPlanInfo.per_night}/night)</div>`;
                }
                
                // Show daily breakdown (optional - can be collapsed)
                html += `
                        <details style="margin-top: 15px;">
                            <summary style="cursor: pointer; color: #667eea; font-weight: 600;">View Daily Breakdown</summary>
                            <div style="margin-top: 10px;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="background: #e0e0e0;">
                                            <th style="padding: 8px; text-align: left;">Date</th>
                                            <th style="padding: 8px; text-align: left;">Available</th>
                                            <th style="padding: 8px; text-align: left;">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;
                
                // Add daily breakdown
                const dailyBreakdown = room.availability.daily_breakdown || {};
                for (const [date, details] of Object.entries(dailyBreakdown)) {
                    html += `
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #e0e0e0;">${date}</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e0e0e0;">${details.available_rooms} rooms</td>
                            <td style="padding: 8px; border-bottom: 1px solid #e0e0e0;">$${details.price}</td>
                        </tr>
                    `;
                }
                
                html += `
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    </div>
                `;
            });
            
            resultsDiv.innerHTML = html;
        }
        
        // Add console logging for debugging
        console.log('Test page loaded successfully');
    </script>
</body>
</html>