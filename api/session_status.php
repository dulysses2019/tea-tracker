<?php
// api/session_status.php
define('TEA_APP_ACCESS', true);
require_once '../config.php';

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'success',
        'loggedin' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ]
    ]);
} else {
    echo json_encode(['status' => 'success', 'loggedin' => false]);
}
?>