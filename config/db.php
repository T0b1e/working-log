<?php
// config/database.php

class Database {
    private $host = 'localhost';
    private $db_name = 'working-log';
    private $username = 'root';  // Use your actual MySQL credentials here
    private $password = '';      // Use your actual MySQL password here
    private $conn;

    // Connect to the database
    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=$this->host;dbname=$this->db_name", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        return $this->conn;
    }
}
