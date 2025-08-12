<?php
session_start();

// Update last activity timestamp before logout
if (isset($_SESSION['customer_id'])) {
    require_once 'Database_Connection.php';
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check if the customers table has a last_logout column
        $checkColumnQuery = "SHOW COLUMNS FROM customers LIKE 'last_logout'";
        $columnResult = $conn->query($checkColumnQuery);
        
        if ($columnResult && $columnResult->num_rows > 0) {
            // Column exists, proceed with update
            $stmt = $conn->prepare("UPDATE customers SET last_logout = CURRENT_TIMESTAMP WHERE id = ?");
            
            if ($stmt) {
                $stmt->bind_param("i", $_SESSION['customer_id']);
                $stmt->execute();
                $stmt->close();
            } else {
                error_log("Failed to prepare logout statement: " . $conn->error);
            }
        } else {
            // Column doesn't exist, log it but continue with logout
            error_log("last_logout column doesn't exist in customers table");
        }
        
        $db->closeConnection();
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>
