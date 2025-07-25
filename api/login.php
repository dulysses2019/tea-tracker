<?php
/**
 * API Endpoint for User Login
 */

define('TEA_APP_ACCESS', true);
require_once '../config.php';

header('Content-Type: application/json');

// 1. Enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// 2. Read and Decode JSON Input
$data = json_decode(file_get_contents('php://input'), true);

// 3. Validation
if ($data === null || !isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. Username and password are required.']);
    exit;
}

$username = trim($data['username']);
$password = $data['password'];

// 4. Database Verification
try {
    $db = DatabaseConnection::getInstance()->getConnection();

    // Find the user by their username
    $stmt = $db->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. Password Verification
    // If a user was found AND the provided password matches the stored hash...
    if ($user && password_verify($password, $user['password_hash'])) {
        
        // 6. Establish the Session
        // This is the moment of authentication.
        // We start a session and store the user's info in the $_SESSION superglobal.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        http_response_code(200); // 200 OK
        echo json_encode([
            'status' => 'success', 
            'message' => 'Login successful.',
            'user' => [ // It's good practice to send back some user info
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ]);

    } else {
        // If no user was found or the password was incorrect...
        http_response_code(401); // 401 Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>