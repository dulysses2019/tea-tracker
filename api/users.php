<?php
// api/users.php
define('TEA_APP_ACCESS', true);
require_once '../config.php';

// Security Gate 1: Must be logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); exit;
}

// Security Gate 2: Must be an executive
if ($_SESSION['role'] !== 'executive') {
    http_response_code(403); // 403 Forbidden
    echo json_encode(['status' => 'error', 'message' => 'You do not have permission to view users.']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = DatabaseConnection::getInstance()->getConnection();
    // We select all fields EXCEPT the password hash for security.
    $stmt = $db->query("SELECT id, username, role, created_at FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $users]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
?>