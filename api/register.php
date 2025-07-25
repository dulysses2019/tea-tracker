<?php
/**
 * API Endpoint for User Registration
 */

define('TEA_APP_ACCESS', true);
require_once '../config.php'; // We need the database connection

// In api/register.php

// Security Gate: Only logged-in executives can register new users.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'executive') {
    http_response_code(403); // 403 Forbidden
    echo json_encode(['status' => 'error', 'message' => 'You do not have permission to register new users.']);
    exit;
}

// ... (the rest of your existing registration code) ...
header('Content-Type: application/json');

// 1. Enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// 2. Read and Decode JSON Input
$data = json_decode(file_get_contents('php://input'), true);

// 3. Server-Side Validation
if ($data === null || !isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. Username and password are required.']);
    exit;
}

$username = trim($data['username']);
$password = $data['password']; // We don't trim passwords

// More validation: ensure username and password are not empty
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username and password cannot be empty.']);
    exit;
}

// 4. Secure Password Hashing
// This is the most critical security step.
// password_hash() creates a strong, salted, one-way hash of the password.
// PASSWORD_DEFAULT is an algorithm that is automatically updated by PHP over time.
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// 5. Database Insertion
try {
    $db = DatabaseConnection::getInstance()->getConnection();

    // Check if username already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        http_response_code(409); // 409 Conflict
        echo json_encode(['status' => 'error', 'message' => 'Username already exists.']);
        exit;
    }

    // Insert the new user with the HASHED password
    $sql = "INSERT INTO users (username, password_hash) VALUES (?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username, $password_hash]);

    http_response_code(201); // 201 Created
    echo json_encode(['status' => 'success', 'message' => 'User registered successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>