<?php
/**
 * API Endpoint to Delete a User
 */

define('TEA_APP_ACCESS', true);
require_once '../config.php';

header('Content-Type: application/json');

// --- SECURITY & AUTHORIZATION ---

// Gate 1: Must be a DELETE request.
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Gate 2: User must be logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

// Gate 3: User must be an executive.
if ($_SESSION['role'] !== 'executive') {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'You do not have permission to delete users.']);
    exit;
}

// --- VALIDATION ---
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'A valid numeric user ID is required.']);
    exit;
}

$user_id_to_delete = (int)$data['id'];
$current_user_id = (int)$_SESSION['user_id'];

// Gate 4: Prevent an executive from deleting themselves.
if ($user_id_to_delete === $current_user_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
    exit;
}

// --- DATABASE EXECUTION ---
try {
    $db = DatabaseConnection::getInstance()->getConnection();
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id_to_delete]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'User and all associated data deleted successfully.']);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>