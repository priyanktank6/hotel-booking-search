<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking Search API</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .endpoints {
            text-align: left;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        .endpoint {
            margin: 15px 0;
            padding: 10px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .method {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
        }
        .post { background: #28a745; color: white; }
        .get { background: #007bff; color: white; }
        .url {
            font-family: monospace;
            color: #667eea;
        }
        .footer {
            margin-top: 30px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏨 Hotel Booking Search API</h1>
        <p class="subtitle">Find the best rooms at the best prices</p>
        
        <a href="/test-search" class="button">🔍 Try Search Interface →</a>
        
        <div class="endpoints">
            <h3>📡 API Endpoints</h3>
            
            <div class="endpoint">
                <span class="method post">POST</span>
                <span class="url">/api/search</span>
                <p style="margin-top: 5px; font-size: 14px;">Search for available rooms</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="url">/api/health</span>
                <p style="margin-top: 5px; font-size: 14px;">Check API health</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="url">/api/db-health</span>
                <p style="margin-top: 5px; font-size: 14px;">Check database connection</p>
            </div>
            
            <div class="endpoint">
                <span class="method get">GET</span>
                <span class="url">/test-search</span>
                <p style="margin-top: 5px; font-size: 14px;">Test interface (GUI)</p>
            </div>
        </div>
        
        <div class="footer">
            Powered by Laravel | Deployed on Render
        </div>
    </div>
</body>
</html>