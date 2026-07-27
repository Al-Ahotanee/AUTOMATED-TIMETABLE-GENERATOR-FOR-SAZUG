<?php
/**
 * Sazug Timetable System - Database Configuration
 * Optimized for Neon PostgreSQL and Render hosting environments.
 */

// Function to establish and return the database connection
function getDBConnection() {
    $dbUrl = getenv('DATABASE_URL');

    if (!$dbUrl) {
        $host = 'localhost';
        $port = '5432';
        $db   = 'sazug_timetable';
        $user = 'postgres';
        $pass = 'root';
    } else {
        $parsedUrl = parse_url($dbUrl);
        $host = $parsedUrl['host'] ?? '';
        $port = $parsedUrl['port'] ?? '5432';
        $db   = ltrim($parsedUrl['path'] ?? '', '/');
        $user = $parsedUrl['user'] ?? '';
        $pass = $parsedUrl['pass'] ?? '';
    }

    $dsn = "pgsql:host=$host;port=$port;dbname=$db";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
        // CRITICAL FIX FOR NEON PGBOUNCER: Must be set to true
        PDO::ATTR_EMULATE_PREPARES   => true,                  
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (\PDOException $e) {
        error_log("Database Connection Failed: " . $e->getMessage());
        die("System is currently unable to connect to the database.");
    }
}
?>
