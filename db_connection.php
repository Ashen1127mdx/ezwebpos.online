<?php
/**
 * Database configuration and connection
 * Update these credentials according to your environment
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'pos';
    private $username = 'root';
    private $password = '';
    private $conn;

    /**
     * Get database connection
     * @return mysqli|null Connection object or null if failed
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            
            // Check connection
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set charset to utf8mb4
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            return null;
        }

        return $this->conn;
    }

    /**
     * Test database connection
     * @return array Status and message
     */
    public function testConnection() {
        $conn = $this->getConnection();
        if ($conn) {
            $conn->close();
            return ['status' => 'success', 'message' => 'Database connected successfully'];
        }
        return ['status' => 'error', 'message' => 'Unable to connect to database'];
    }
}

// Create a global instance for easy access
$database = new Database();
$db = $database->getConnection();
?>