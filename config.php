<?php
/**
 * Tea Business Application Configuration
 * 
 * SECURITY NOTICE:
 * - Keep this file outside the web root if possible
 * - Set proper file permissions (644 or 600)
 * - Never commit actual passwords to version control
 * - Use environment variables in production
 */

// Prevent direct access
if (!defined('TEA_APP_ACCESS')) {
    die('Direct access not allowed');
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
} 



// Database Configuration
define('DB_HOST', 'db'); // Use the service name 'db' from docker-compose
define('DB_NAME', getenv('MYSQL_DATABASE') ?? 'tea_tracker');
define('DB_USER', getenv('MYSQL_USER') ?? 'tea_app');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?? 'Quan::5522'); // Fallback for non-Docker
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Tea Tracker');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'America/Los_Angeles');  // Change to your timezone

// Security Configuration
define('ENABLE_CORS', true);  // Set to false in production if not needed
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://127.0.0.1',
    'https://yourdomain.com'  // Add the actual domain
]);

// Session Configuration (if you add user authentication later)
define('SESSION_LIFETIME', 3600);  // 1 hour
define('SESSION_NAME', 'tea_tracker_session');

// Error Configuration
define('DEBUG_MODE', true);  // Set to false in production
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', __DIR__ . '/logs/error.log');

// API Configuration
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100);  // Requests per minute
define('API_TIMEOUT', 30);  // Seconds

// File Upload Configuration (if needed later)
define('MAX_FILE_SIZE', 2097152);  // 2MB
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Database Connection Class
class DatabaseConnection {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Set timezone
            $this->pdo->exec("SET time_zone = '" . date('P') . "'");
            
        } catch (PDOException $e) {
            // Log the detailed error for the developer
            error_log("Database connection failed: " . $e->getMessage());
            // Throw a new, more generic exception to the calling script
            // This prevents leaking detailed database errors to the client.
            throw new Exception("Could not connect to the database.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// Utility Functions
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateCSRFToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function cors() {
    if (ENABLE_CORS) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array($origin, ALLOWED_ORIGINS)) {
            header("Access-Control-Allow-Origin: $origin");
        }
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }
    }
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function logError($message, $context = []) {
    if (LOG_ERRORS) {
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
        if (!empty($context)) {
            $logMessage .= ' - Context: ' . json_encode($context);
        }
        error_log($logMessage . PHP_EOL, 3, ERROR_LOG_FILE);
    }
}

// Rate Limiting (simple implementation without APCu)
function checkRateLimit($identifier, $limit = API_RATE_LIMIT) {
    // Create a directory for storing rate limit data if it doesn't exist
    $dir = __DIR__ . '/rate_limits';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $key = 'rate_limit_' . md5($identifier);
    $file = $dir . '/' . $key;
    
    // Check if file exists and is less than 1 minute old
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        $now = time();
        
        // If data is older than 1 minute, reset it
        if ($now - $data['time'] > 60) {
            $data = ['count' => 1, 'time' => $now];
            file_put_contents($file, json_encode($data));
            return true;
        }
        
        // If limit is reached, return false
        if ($data['count'] >= $limit) {
            return false;
        }
        
        // Increment count
        $data['count']++;
        file_put_contents($file, json_encode($data));
        return true;
    } else {
        // Create new file
        $data = ['count' => 1, 'time' => time()];
        file_put_contents($file, json_encode($data));
        return true;
    }
}

// Initialize timezone
date_default_timezone_set(APP_TIMEZONE);

// Create logs directory if it doesn't exist
$logDir = dirname(ERROR_LOG_FILE);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Enable CORS
cors();


// Set error reporting based on debug mode
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>