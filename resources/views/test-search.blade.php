<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hotel Booking Search</title>
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
            max-width: 1400px;
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
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            background: #667eea;
            color: white;
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
        }
        
        .room-option-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .room-option-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .room-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .rate-plan-badge {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .occupancy-info {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .price-breakdown {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .price-row:last-child {
            border-bottom: none;
        }
        
        .total-price {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            margin-top: 10px;
        }
        
        .discount-badge {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-block;
            margin: 10px 0;
        }
        
        .row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-message {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        details {
            margin-top: 15px;
        }
        
        summary {
            cursor: pointer;
            color: #667eea;
            font-weight: 600;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: #f0f0f0;
        }
        
        .available-badge {
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="search-card">
            <h1>🏨 Hotel Booking Search</h1>
            <p class="subtitle">Variable Occupancy | Multiple Rate Plans | Configurable Discounts</p>
            
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
                        <small style="color: #666;">Standard: max 3 | Deluxe: max 4</small>
                    </div>
                    
                    <div class="form-group">
                        <label>🍽️ Rate Plan (Optional)</label>
                        <select id="rate_plan_code">
                            <option value="">All Rate Plans</option>
                            <option value="EP">EP - Room Only</option>
                            <option value="CP">CP - Breakfast Included</option>
                            <option value="MAP">MAP - All Meals Included</option>
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
        // Set default dates
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
            const ratePlanCode = document.getElementById('rate_plan_code').value;
            
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
            
            const requestBody = {
                check_in: checkIn,
                check_out: checkOut,
                adults: parseInt(adults)
            };
            
            if (ratePlanCode) {
                requestBody.rate_plan_code = ratePlanCode;
            }
            
            try {
                const response = await fetch('/api/search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(requestBody)
                });
                
                const data = await response.json();

                // DEBUG: Log the actual response
                console.log('API Response:', data);
                
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
                resultsDiv.innerHTML = `<div class="error-message">❌ Connection Error: ${error.message}</div>`;
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
            
            // Check if available_options exists (Round 2 structure) or available_room_types (Round 1)
            const options = data.available_options || data.available_room_types;
            
            if (!options || options.length === 0) {
                resultsDiv.innerHTML = `
                    <div class="info-message">
                        <strong>ℹ️ No rooms available</strong><br><br>
                        <strong>Search Criteria:</strong><br>
                        Check-in: ${data.search_criteria.check_in}<br>
                        Check-out: ${data.search_criteria.check_out}<br>
                        Nights: ${data.search_criteria.nights}<br>
                        Adults: ${data.search_criteria.adults}<br><br>
                        <strong>Possible reasons:</strong><br>
                        • No rooms available for these dates<br>
                        • Maximum occupancy exceeded (Standard: 3, Deluxe: 4)<br>
                        • Selected rate plan not available<br>
                        • Try different dates
                    </div>
                `;
                return;
            }
            
             // Display search criteria
            let html = `
                <div class="info-message">
                    <strong>✅ Found ${options.length} option(s)</strong>
                </div>
                
                <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                    <strong>📋 Search Summary:</strong><br>
                    Check-in: ${data.search_criteria.check_in}<br>
                    Check-out: ${data.search_criteria.check_out}<br>
                    Nights: ${data.search_criteria.nights}<br>
                    Adults: ${data.search_criteria.adults}
                </div>
            `;
            
            options.forEach((option) => {
                const pricing = option.pricing || {};
                const breakdown = pricing.breakdown || {};
                const nightlyRate = pricing.nightly_rate || {};
                const discounts = pricing.discounts || {};
                const appliedDiscounts = discounts.applied || [];
                const ratePlanInfo = option.rate_plan || {};
                const roomTypeInfo = option.room_type || {};
                const availability = option.availability || {};
                
                html += `
                    <div class="room-option-card">
                        <div class="room-header">
                            <div>
                                <span class="room-title">${roomTypeInfo.name || 'Room'}</span>
                                <span class="rate-plan-badge">${ratePlanInfo.code || 'N/A'} - ${ratePlanInfo.name || 'Rate Plan'}</span>
                            </div>
                            <div>
                                <span class="available-badge">
                                    ${availability.available_rooms || 0} rooms available
                                </span>
                            </div>
                        </div>
                        
                        <div class="occupancy-info">
                            👥 Occupancy: ${roomTypeInfo.min_occupancy || 1} - ${roomTypeInfo.max_occupancy || 3} adults
                        </div>
                        
                        <div class="price-breakdown">
                            <div class="price-row">
                                <span>Room Subtotal (${option.stay_details?.nights || 0} nights)</span>
                                <span>$${breakdown.room_subtotal || 0}</span>
                            </div>
                            
                            ${ratePlanInfo.meal_charge_per_night > 0 ? `
                            <div class="price-row">
                                <span>${ratePlanInfo.name} ($${ratePlanInfo.meal_charge_per_night}/night)</span>
                                <span>+$${breakdown.meal_plan_charge || 0}</span>
                            </div>
                            ` : ''}
                            
                            ${appliedDiscounts.length > 0 ? appliedDiscounts.map(discount => `
                            <div class="price-row" style="color: #dc3545;">
                                <span>🎉 ${discount.description}</span>
                                <span>-$${discount.amount}</span>
                            </div>
                            `).join('') : ''}
                            
                            <div class="price-row" style="border-top: 2px solid #ddd; margin-top: 8px; padding-top: 8px; font-weight: bold;">
                                <span>Total</span>
                                <span class="total-price">$${breakdown.total || 0}</span>
                            </div>
                            
                            <div style="margin-top: 10px; color: #666; font-size: 14px;">
                                Average nightly rate: $${nightlyRate.average || 0}
                            </div>
                        </div>
                        
                        ${appliedDiscounts.length > 0 ? `
                        <div class="discount-badge">
                            💰 Total Saved: $${discounts.total_saved || 0}
                        </div>
                        ` : ''}
                        
                        <details>
                            <summary>View Daily Breakdown</summary>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Available Rooms</th>
                                        <th>Base Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${Object.entries(availability.daily_breakdown || {}).map(([date, details]) => `
                                    <tr>
                                        <td>${date}</td>
                                        <td>${details.available_rooms}</td>
                                        <td>$${details.price}</td>
                                    </tr>
                                    `).join('')}
                                </tbody>
                            </table>
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