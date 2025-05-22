<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'foundit');

// Create connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log error (you might want to implement proper error logging)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Show user-friendly error message
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to close the database connection
function close_connection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}

// Register shutdown function to ensure connection is closed
register_shutdown_function('close_connection');
?> 