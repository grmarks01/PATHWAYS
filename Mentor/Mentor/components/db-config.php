<?php
/**
 * Database Configuration File
 * 
 * Central location for all database connection settings.
 * Include this file in any PHP file that needs database access.
 * 
 * Usage: require_once 'db-config.php';
 *        $conn = getDBConnection();
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pathways_opportunities');

/**
 * Get a new database connection
 * 
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        // In production, you might want to log this instead of displaying it
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4 for full Unicode support
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * Close database connection
 * 
 * @param mysqli $conn Database connection to close
 */
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
