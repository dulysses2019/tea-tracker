<?php
// Define access constant
define('TEA_APP_ACCESS', true);

// Include configuration
require_once '../config.php';

// Set content type
header('Content-Type: application/json');

// NEW, SECURE, ROLE-AWARE LOGIC
try {
    // 1. First, we must check if the user is logged in at all.
    // This is the security gate for this endpoint.
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'You must be logged in to view products.']);
        exit;
    }

    // 2. Retrieve the user's ID and role from the session we established at login.
    // ... (session check) ...
        $db = DatabaseConnection::getInstance()->getConnection();

    // The product catalog is global, so no user filter is needed.
    $sql = "SELECT * FROM tea_products WHERE is_active = 1";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Return the result.
    echo json_encode(['status' => 'success', 'data' => $products]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>