<?php
/**
 * Database Seeder Script
 *
 * This script should ONLY be run from the command line (CLI).
 * It populates the database with default users and passwords.
 * Usage: php seed_database.php
 */

// --- SECURITY GATE ---
// This is a critical check. It ensures this powerful script cannot be run from a web browser.
if (php_sapi_name() !== 'cli') {
    die("This script can only be executed from the command line.");
}

// We need the application's configuration and database connection.
define('TEA_APP_ACCESS', true);
require_once 'config.php';

echo "Starting database seeding...\n";

try {
    $db = DatabaseConnection::getInstance()->getConnection();

    // --- Define the Default Users and Password ---
    $default_password = 'password123';
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

    $users_to_seed = [
        ['username' => 'exec_user', 'hash' => $hashed_password, 'role' => 'executive'],
        ['username' => 'emp_user',  'hash' => $hashed_password, 'role' => 'employee']
    ];

    echo "Default password is: '" . $default_password . "'\n";
    echo "Generated hash is: " . $hashed_password . "\n";

    // --- Prepare the Idempotent SQL Query ---
    // This is an "INSERT ... ON DUPLICATE KEY UPDATE" query.
    // It attempts to insert a new user. If a user with that UNIQUE username
    // already exists, it will UPDATE their password_hash instead.
    // This makes the seeder safe to run multiple times.
    $sql = "INSERT INTO users (username, password_hash, role) VALUES (:username, :hash, :role)
            ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role)";
    
    $stmt = $db->prepare($sql);

    // --- Loop and Execute ---
    foreach ($users_to_seed as $user) {
        $stmt->execute([
            ':username' => $user['username'],
            ':hash' => $user['hash'],
            ':role' => $user['role']
        ]);
        echo "Successfully seeded user: " . $user['username'] . "\n";
    }

    echo "\nDatabase seeding completed successfully!\n";

} catch (Exception $e) {
    echo "\nERROR: Database seeding failed.\n";
    echo "Message: " . $e->getMessage() . "\n";
    exit(1); // Exit with an error code
}

exit(0); // Exit with a success code
?>