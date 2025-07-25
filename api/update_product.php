<?php
/**
 * API Endpoint to Update an Existing Tea Product
 */

define('TEA_APP_ACCESS', true);
require_once '../config.php';

header('Content-Type: application/json');

// We are using POST for this operation as established.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// --- IDENTIFIER VALIDATION ---
// The ID comes from the query string.
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'A valid product ID is required.']);
    exit;
}
$id = (int)$_GET['id'];

// --- DATA PAYLOAD VALIDATION ---
$data = json_decode(file_get_contents('php://input'), true);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload.']);
    exit;
}

// --- DYNAMIC SQL CONSTRUCTION ---
// This is the new, complex part.
$fields = [];
$params = [];

// We build a list of fields to update based on the data we received.
if (isset($data['name'])) {
    $fields[] = "name = ?";
    $params[] = trim($data['name']);
}
if (isset($data['description'])) {
    $fields[] = "description = ?";
    $params[] = trim($data['description']);
}
if (isset($data['vendor_cost']) && is_numeric($data['vendor_cost'])) {
    $fields[] = "vendor_cost = ?";
    $params[] = (float)$data['vendor_cost'];
}
if (isset($data['selling_price']) && is_numeric($data['selling_price'])) {
    $fields[] = "selling_price = ?";
    $params[] = (float)$data['selling_price'];
}

// If no valid fields were provided to update, it's a bad request.
if (empty($fields)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No valid fields provided for update.']);
    exit;
}

// We add the ID to the end of our parameters list for the WHERE clause.
$params[] = $id;

// We join the field strings with commas to build the SET part of the query.
$sql = "UPDATE tea_products SET " . implode(', ', $fields) . " WHERE id = ?";

// --- DATABASE EXECUTION ---
try {
    $db = DatabaseConnection::getInstance()->getConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Check if any rows were actually affected.
    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Tea product updated successfully.']);
    } else {
        // This can happen if the ID doesn't exist or the data was the same.
        http_response_code(200); // Still a success, just no change.
        echo json_encode(['status' => 'success', 'message' => 'No changes were made to the product.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>