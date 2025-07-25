<?php
/**
 * Main Application Entry Point & Controller
 */
//key to get in
define('TEA_APP_ACCESS', true);

//load the configuration and all tools.
require_once 'config.php';
// In index.php, right after require_once 'config.php';

// Since config.php now calls session_start(), we can check the session.
if (!isset($_SESSION['user_id'])) {
    // If the user is not logged in, redirect them to the login page.
    header('Location: login.html');
    exit; 
}

$pageTitle = APP_NAME;
$username = $_SESSION['username'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tea Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #2c5530, #4a7c59);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            <!-- In index.php, inside the .header div -->
        
        }

        .header h1 {
            color: #2c5530;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .date-display {
            text-align: center;
            font-size: 1.2em;
            color: #666;
            margin-bottom: 20px;
        }

        .tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .tab {
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .tab.active {
            background: #2c5530;
            color: white;
            transform: translateY(-2px);
        }

        .tab:hover {
            background: #4a7c59;
            color: white;
        }

        .tab-content {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .tea-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .tea-card:hover {
            transform: translateY(-5px);
        }

        .tea-name {
            font-size: 1.3em;
            font-weight: bold;
            color: #2c5530;
            margin-bottom: 10px;
        }

        .tea-prices {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #666;
        }

        .inventory-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .inventory-input:focus {
            outline: none;
            border-color: #4a7c59;
        }

        .sales-section {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .sales-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .sales-card:hover {
            transform: translateY(-5px);
        }

        .stock-display {
            font-size: 1.1em;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stock-high { color: #28a745; }
        .stock-medium { color: #ffc107; }
        .stock-low { color: #dc3545; }

        .sell-button {
            background: linear-gradient(45deg, #28a745, #34ce57);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            transform: scale(1);
        }

        .sell-button:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4);
        }

        .sell-button:active {
            transform: scale(0.95);
        }

        .sell-button:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .summary-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .summary-value {
            font-size: 2em;
            font-weight: bold;
            color: #2c5530;
            margin-bottom: 5px;
        }

        .summary-label {
            color: #666;
            font-size: 0.9em;
        }

        .action-button {
            background: linear-gradient(45deg, #2c5530, #4a7c59);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 10px 5px;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(44, 85, 48, 0.4);
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            display: none;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            display: none;
        }

        .delete-button {
            background: linear-gradient(45deg, #dc3545, #e74c3c);
            color: white;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .delete-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(220, 53, 69, 0.4);
        }

        .tea-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #2c5530;
        }

        .tea-details {
            flex: 1;
        }

        .tea-pricing {
            display: flex;
            gap: 20px;
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .tabs {
                flex-direction: column;
                align-items: center;
            }
            
            .tab {
                width: 200px;
            }
            
            .inventory-grid,
            .sales-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍃 Tea Tracker</h1>
            <div class="date-display">Today: <span id="currentDate"></span></div>
            <button class="action-button" style="float: right;" onclick="handleLogout()">Logout</button>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('inventory')">📦 Daily Inventory</button>
            <button class="tab" onclick="showTab('sales')">💰 Sales</button>
            <button class="tab" onclick="showTab('summary')">📊 Summary</button>
            <button class="tab" onclick="showTab('manage')">⚙️ Manage Teas</button>
            <button class="tab" onclick="showTab('dashboard')" style="display: none;">📈 Dashboard</button>
            <button class="tab" onclick="showTab('account')" style="display: none;">👤 Account</button>
        </div>

        <!-- Inventory Tab -->
        <div id="inventory" class="tab-content active">
            <h2>Daily Inventory Setup</h2>
            <div style="margin-bottom: 20px;">
                <label for="inventoryDate" style="font-weight: bold;">Select Date:</label>
                <input type="date" id="inventoryDate" class="inventory-input" style="width: auto;">
            </div>
            <p>Enter how much of each tea you're buying for today's market:</p>
            
            <div class="inventory-grid" id="inventoryGrid">
                <!-- Tea cards will be populated here -->
            </div>
            
            <button class="action-button" onclick="saveInventory()">💾 Save Today's Inventory</button>
            <div id="inventoryMessage" class="success-message"></div>
        </div>

        <!-- Sales Tab -->
        <div id="sales" class="tab-content">
            <h2>Sales Terminal</h2>
            <div style="margin-bottom: 20px;">
                <label for="salesDate" style="font-weight: bold;">Select Date:</label>
                <input type="date" id="salesDate" class="inventory-input" style="width: auto;">
            </div>
            <p>Click the button each time you sell a tea bag:</p>
            
            <div class="sales-section" id="salesSection">
                <!-- Sales cards will be populated here -->
            </div>
            
            <div id="salesMessage" class="success-message"></div>
        </div>

        <!-- Summary Tab -->
        <div id="summary" class="tab-content">
            <h2>Daily Summary</h2>
            <div style="margin-bottom: 20px;">
                <label for="summaryDate" style="font-weight: bold;">Select Date:</label>
                <input type="date" id="summaryDate" class="inventory-input" style="width: auto;">
            </div>
            <div class="summary-card">
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value" id="totalCost">$0.00</div>
                        <div class="summary-label">Total Inventory Cost</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="totalRevenue">$0.00</div>
                        <div class="summary-label">Total Revenue</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="totalProfit">$0.00</div>
                        <div class="summary-label">Total Profit</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="totalSold">0</div>
                        <div class="summary-label">Units Sold</div>
                    </div>
                </div>
            </div>
            
            <button class="action-button" onclick="refreshSummary()">🔄 Refresh Summary</button>
        </div>

        <!-- Manage Teas Tab -->
        <div id="manage" class="tab-content">
            <h2>Manage Tea Types</h2>
            
            <div class="summary-card">
                <h3>Add New Tea</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                    <input type="text" id="newTeaName" placeholder="Tea Name" class="inventory-input">
                    <input type="text" id="newTeaDescription" placeholder="Description" class="inventory-input">
                    <input type="number" id="newTeaBuyCost" placeholder="Buy Cost ($)" step="0.01" min="0" class="inventory-input">
                    <input type="number" id="newTeaSellPrice" placeholder="Sell Price ($)" step="0.01" min="0" class="inventory-input">
                </div>
                <button class="action-button" onclick="addNewTea()">➕ Add Tea</button>
                <div id="manageMessage" class="success-message"></div>
            </div>
            <div class="summary-card">
                <h3>Current Tea Types</h3>
                <div id="teaList" style="margin-top: 20px;">
                    <!-- Tea list will be populated here -->
                </div>
            </div>
        </div>
         <div id="account" class="tab-content">
            <!-- This container is intentionally left empty. 
                 The new 'loadAccountTab()' JavaScript function 
                 will be responsible for building and injecting 
                 the HTML content here. -->
        </div>
        <div id="dashboard" class="tab-content">
            <h2>Executive Dashboard</h2>
            <div style="margin-bottom: 20px;">
                <label for="dashboardDate" style="font-weight: bold;">Select Date:</label>
                <input type="date" id="dashboardDate" class="inventory-input" style="width: auto;">
            </div>

            <!-- Grand Totals Summary -->
            <div class="summary-card">
                <h3>Grand Totals for All Users</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value" id="dashTotalCost">$0.00</div>
                        <div class="summary-label">Total Inventory Cost</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="dashTotalRevenue">$0.00</div>
                        <div class="summary-label">Total Revenue</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="dashTotalProfit">$0.00</div>
                        <div class="summary-label">Total Profit</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value" id="dashTotalSold">0</div>
                        <div class="summary-label">Total Units Sold</div>
                    </div>
                </div>
            </div>

            <!-- User-by-User Breakdown -->
            <div class="summary-card">
                <h3>Per-Employee Breakdown</h3>
                <div style="overflow-x: auto;"> <!-- For responsiveness on small screens -->
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <thead>
                            <tr style="background-color: #f2f2f2;">
                                <th style="padding: 12px; text-align: left;">Employee</th>
                                <th style="padding: 12px; text-align: right;">Inv. Cost</th>
                                <th style="padding: 12px; text-align: right;">Revenue</th>
                                <th style="padding: 12px; text-align: right;">Profit</th>
                                <th style="padding: 12px; text-align: right;">Units Purchased</th>
                                <th style="padding: 12px; text-align: right;">Units Sold</th>
                            </tr>
                        </thead>
                        <tbody id="dashboardUserTableBody">
                            <!-- User data will be injected here by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <script src="assets/js/app.js"></script>
</body>
</html>
