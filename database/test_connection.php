<?php
require_once 'connection.php';

try {
    // Test database connection
    if ($conn) {
        echo "Database connection successful!<br>";
        
        // Test if accounts table exists
        $result = $conn->query("SHOW TABLES LIKE 'accounts'");
        if ($result->num_rows > 0) {
            echo "Accounts table exists!<br>";
            
            // Show table structure
            $result = $conn->query("DESCRIBE accounts");
            echo "Table structure:<br>";
            while ($row = $result->fetch_assoc()) {
                echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
            }
        } else {
            echo "Error: Accounts table does not exist!<br>";
        }
    } else {
        echo "Database connection failed!<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 