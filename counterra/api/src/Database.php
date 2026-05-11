<?php
namespace App;

use PDO;
use PDOException;

class Database {
    private string $host = "localhost";
    private string $db_name = "db_countera";
    private string $username = "root";
    private string $password = ""; 
    public ?PDO $conn = null;

    public function getConnection(): ?PDO {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            // Set error mode to exception so we can see what goes wrong
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Fetch as associative arrays by default
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}