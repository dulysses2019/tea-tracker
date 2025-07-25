<?php
// api/change_password.php
define('TEA_APP_ACCESS', true);
require_once '../config.php';

// Security Gate: Must be logged in to change a password.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validation
if (!isset($data['current_password']) || !isset($data['new_password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Current and new passwords are required.']);
    exit;
}

$current_password = $data['current_password'];
$new_password = $data['new_password'];

if (empty($new_password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'New password cannot be empty.']);
    exit;
}

$user_id = $_SESSION['user_id']; // Get the ID of the currently logged-in user.

try {
    $db = DatabaseConnection::getInstance()->getConnection();

    // 1. Fetch the current user's stored password hash.
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Verify the "current_password" provided by the user.
    if (!$user || !password_verify($current_password, $user['password_hash'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Incorrect current password.']);
        exit;
    }

    // 3. If verification passes, hash the NEW password.
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // 4. Update the database with the new hash.
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$new_password_hash, $user_id]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Password changed successfully.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
?>