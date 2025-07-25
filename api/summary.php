<?php
/**
 * API Endpoint for Daily Summary
 */

define('TEA_APP_ACCESS', true);
require_once '../config.php';

// --- Security Gate: User must be logged in ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

// Retrieve user info from the session
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

header('Content-Type: application/json');

$date = $_GET['date'] ?? date('Y-m-d');

try {
    $db = DatabaseConnection::getInstance()->getConnection();
    $summary = [];

    // --- AUTHORIZATION PATTERN for AGGREGATE QUERIES ---
    $userFilterSql = '';
    $params = [$date];

    if ($current_user_role !== 'executive') {
        $userFilterSql = " AND user_id = ?";
        $params[] = $current_user_id;
    }

    // 1. Calculate Total Inventory Cost
    $costSql = "SELECT SUM(i.quantity_purchased * p.vendor_cost) as total_cost
                FROM daily_inventory i
                JOIN tea_products p ON i.tea_product_id = p.id
                WHERE i.market_date = ? $userFilterSql";
    $costStmt = $db->prepare($costSql);
    $costStmt->execute($params);
    $summary['total_inventory_cost'] = $costStmt->fetchColumn() ?? 0;

    // 2. Calculate Total Revenue and Units Sold
    $revSql = "SELECT SUM(unit_price * quantity_sold) as total_revenue, SUM(quantity_sold) as total_units
               FROM sales
               WHERE market_date = ? $userFilterSql";
    $revStmt = $db->prepare($revSql);
    $revStmt->execute($params);
    $revResult = $revStmt->fetch(PDO::FETCH_ASSOC);
    $summary['total_revenue'] = $revResult['total_revenue'] ?? 0;
    $summary['total_units_sold'] = $revResult['total_units'] ?? 0;

    // 3. Calculate Total Profit
    $summary['total_profit'] = $summary['total_revenue'] - $summary['total_inventory_cost'];

    echo json_encode(['status' => 'success', 'data' => ['summary' => $summary]]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>