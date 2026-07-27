<?php
/**
 * Sazug Timetable System - Database Configuration
 * Optimized for Neon PostgreSQL and Render hosting environments.
 */

// Function to establish and return the database connection
function getDBConnection() {
    // Determine the database URL. 
    // Render/Neon typically provide the connection string via the DATABASE_URL environment variable.
    $dbUrl = getenv('DATABASE_URL');

    if (!$dbUrl) {
        // Fallback for local development if environment variable is not set
        // Update these with your local PostgreSQL credentials if running locally
        $host = 'localhost';
        $port = '5432';
        $db   = 'sazug_timetable';
        $user = 'postgres';
        $pass = 'root';
    } else {
        // Parse the provided database URL
        $parsedUrl = parse_url($dbUrl);
        
        $host = $parsedUrl['host'] ?? '';
        $port = $parsedUrl['port'] ?? '5432';
        $db   = ltrim($parsedUrl['path'] ?? '', '/');
        $user = $parsedUrl['user'] ?? '';
        $pass = $parsedUrl['pass'] ?? '';
    }

    // Construct the PostgreSQL DSN (Data Source Name)
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements for security
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // In a production environment, log this instead of outputting directly to the screen
        error_log("Database Connection Failed: " . $e->getMessage());
        die("System is currently unable to connect to the database. Please try again later.");
    }
}
?>
