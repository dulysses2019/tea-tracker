<?php
/**
 * API Endpoint for the Executive Dashboard Report.
 * Provides a full breakdown of all user activity for a given date.
 */

define('TEA_APP_ACCESS', true);
require_once '../config.php';

header('Content-Type: application/json');

// --- SECURITY & AUTHORIZATION ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); exit;
}
if ($_SESSION['role'] !== 'executive') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied.']);
    exit;
}

// --- LOGIC ---
$date = $_GET['date'] ?? date('Y-m-d');

try {
    $db = DatabaseConnection::getInstance()->getConnection();

    // CORRECTED QUERY with unique placeholder names
    $sql = "
        SELECT
            u.id,
            u.username,
            COALESCE(inv.total_units_purchased, 0) AS total_units_purchased,
            COALESCE(inv.total_inventory_cost, 0) AS total_inventory_cost,
            COALESCE(sal.total_units_sold, 0) AS total_units_sold,
            COALESCE(sal.total_revenue, 0) AS total_revenue,
            (COALESCE(sal.total_revenue, 0) - COALESCE(inv.total_inventory_cost, 0)) AS profit
        FROM
            users u
        LEFT JOIN (
            SELECT
                di.user_id,
                SUM(di.quantity_purchased) AS total_units_purchased,
                SUM(di.quantity_purchased * tp.vendor_cost) AS total_inventory_cost
            FROM
                daily_inventory di
            JOIN
                tea_products tp ON di.tea_product_id = tp.id
            WHERE
                di.market_date = :market_date_inv -- UNIQUE NAME 1
            GROUP BY
                di.user_id
        ) AS inv ON u.id = inv.user_id
        LEFT JOIN (
            SELECT
                s.user_id,
                SUM(s.quantity_sold) AS total_units_sold,
                SUM(s.unit_price * s.quantity_sold) AS total_revenue
            FROM
                sales s
            WHERE
                s.market_date = :market_date_sales -- UNIQUE NAME 2
            GROUP BY
                s.user_id
        ) AS sal ON u.id = sal.user_id
        WHERE
            u.role = 'employee' OR u.id = :current_user_id -- UNIQUE NAME 3
        ORDER BY
            u.username;
    ";

    $stmt = $db->prepare($sql);

    // CORRECTED execute() call with a value for each unique placeholder
    $stmt->execute([
        ':market_date_inv' => $date,
        ':market_date_sales' => $date,
        ':current_user_id' => $_SESSION['user_id']
    ]);
    
    $user_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate grand totals (this part remains the same)
    $grand_totals = [
        'total_inventory_cost' => 0,
        'total_revenue' => 0,
        'total_profit' => 0,
        'total_units_sold' => 0,
        'total_units_purchased' => 0,
    ];

    foreach ($user_breakdown as $user) {
        $grand_totals['total_inventory_cost'] += $user['total_inventory_cost'];
        $grand_totals['total_revenue'] += $user['total_revenue'];
        $grand_totals['total_profit'] += $user['profit'];
        $grand_totals['total_units_sold'] += $user['total_units_sold'];
        $grand_totals['total_units_purchased'] += $user['total_units_purchased'];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'grand_totals' => $grand_totals,
            'user_breakdown' => $user_breakdown,
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>