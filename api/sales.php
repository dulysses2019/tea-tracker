<?php
/**
 * API Endpoint for Sales Records
 * Handles POST to create a new sale.
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

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validation...
if (!isset($data['tea_product_id'], $data['quantity_sold'], $data['market_date'], $data['unit_price'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields for sale.']);
    exit;
}

$db = DatabaseConnection::getInstance()->getConnection();

// We wrap this in a transaction to ensure data integrity.
$db->beginTransaction();

try {
    // --- Step 1 - Fetch product cost and calculate revenue ---
    $costStmt = $db->prepare("SELECT vendor_cost FROM tea_products WHERE id = ?");
    $costStmt->execute([(int)$data['tea_product_id']]);
    $product = $costStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found, cannot record sale.');
    }
    
    $unit_cost = (float)$product['vendor_cost'];
    $total_revenue = (int)$data['quantity_sold'] * (float)$data['unit_price'];

    // --- Step 2 - Decrement the inventory for the logged-in user ---
    $invSql = "UPDATE daily_inventory 
               SET quantity_remaining = quantity_remaining - ? 
               WHERE tea_product_id = ? AND user_id = ? AND market_date = ? AND quantity_remaining >= ?";
    
    $invStmt = $db->prepare($invSql);
    $invStmt->execute([
        (int)$data['quantity_sold'],
        (int)$data['tea_product_id'],
        $current_user_id,
        $data['market_date'],
        (int)$data['quantity_sold']
    ]);

    if ($invStmt->rowCount() === 0) {
        throw new Exception('Not enough stock available to make the sale.');
    }

    // --- Step 3 - Insert the complete sales record ---
    $saleSql = "INSERT INTO sales (tea_product_id, user_id, quantity_sold, unit_price, total_revenue, unit_cost, market_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $saleStmt = $db->prepare($saleSql);
    $saleStmt->execute([
        (int)$data['tea_product_id'],
        $current_user_id,
        (int)$data['quantity_sold'],
        (float)$data['unit_price'],
        $total_revenue,
        $unit_cost,
        $data['market_date']
    ]);

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => 'Sale recorded successfully.']);

} catch (Exception $e) {
    // If anything failed, roll back the transaction.
    $db->rollBack();
    http_response_code(400);
    // CORRECTED LINE: Use the correct variable $e
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?> 