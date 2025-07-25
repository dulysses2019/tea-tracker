<?php
// api/logout.php
session_start(); // Resume the session
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session itself
http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Logged out successfully.']);
?>