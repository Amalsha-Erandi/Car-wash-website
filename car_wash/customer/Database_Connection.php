<?php

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "smart_wash";
    private $conn;

    // Constructor to initiate the database connection
    public function __construct() {
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set UTF-8 encoding
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Unable to connect to database. Please try again later.");
        }
    }

    // Method to get the connection
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $this->conn;
    }

    // Method to close the database connection
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    // Helper function to safely escape values
    public function escapeString($value) {
        return $this->conn->real_escape_string($value);
    }
    
    // Helper function to get last insert ID
    public function getLastInsertId() {
        return $this->conn->insert_id;
    }
    
    // Helper function to begin transaction
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    // Helper function to commit transaction
    public function commit() {
        $this->conn->commit();
    }
    
    // Helper function to rollback transaction
    public function rollback() {
        $this->conn->rollback();
    }
}

// // Example usage
// $db = new Database();
// $conn = $db->getConnection();

// // Perform your queries here

// // Close the connection
// $db->closeConnection();
?>
