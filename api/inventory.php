<?php
/**
 * API Endpoint for Daily Inventory
 * Handles GET to fetch inventory and POST to create/update it.
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
$db = DatabaseConnection::getInstance()->getConnection();

// --- Method Routing ---
// This is how a single file can handle multiple types of requests.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    handleGetInventory($db, $current_user_id, $current_user_role);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePostInventory($db, $current_user_id);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

// --- GET Logic ---
function handleGetInventory($db, $current_user_id, $current_user_role) {
    $date = $_GET['date'] ?? date('Y-m-d');

    // ** AUTHORIZATION PATTERN **
    $sql = "SELECT * FROM daily_inventory WHERE market_date = ?";
    $params = [$date];

    if ($current_user_role !== 'executive') {
        $sql .= " AND user_id = ?";
        $params[] = $current_user_id;
    }

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $inventory]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// --- POST Logic ---
// In api/inventory.php

// --- POST Logic ---
// In api/inventory.php

function handlePostInventory($db, $current_user_id) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['tea_product_id'], $data['quantity_purchased'], $data['market_date'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    $tea_id = (int)$data['tea_product_id'];
    $quantity = (int)$data['quantity_purchased'];
    $date = $data['market_date'];

    try {
        $productStmt = $db->prepare("SELECT vendor_cost FROM tea_products WHERE id = ?");
        $productStmt->execute([$tea_id]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Tea product not found.']);
            exit;
        }

        $total_cost = $quantity * (float)$product['vendor_cost'];

        // --- THIS IS THE NEW, CORRECTED SQL ---
        $sql = "
            INSERT INTO daily_inventory 
                (tea_product_id, user_id, quantity_purchased, total_cost, quantity_remaining, market_date)
            VALUES 
                (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                quantity_remaining = quantity_remaining + (VALUES(quantity_purchased) - quantity_purchased),
                total_cost = VALUES(total_cost),
                quantity_purchased = VALUES(quantity_purchased)
        ";
        
        $stmt = $db->prepare($sql);
        // The parameters are the same as before
        $stmt->execute([$tea_id, $current_user_id, $quantity, $total_cost, $quantity, $date]);
        
        echo json_encode(['status' => 'success', 'message' => 'Inventory updated successfully.']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>