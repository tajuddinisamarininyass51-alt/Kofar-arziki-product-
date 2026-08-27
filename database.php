<?php
/**
 * Database Connection Handler
 * Uses PDO for secure database connections
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'kofar_arziki';
    private $db_user = 'root';
    private $db_pass = '';
    private $pdo;

    public function connect() {
        $this->pdo = null;

        try {
            $this->pdo = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name,
                $this->db_user,
                $this->db_pass
            );
            
            // Set error mode to exception
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set default fetch mode
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            return $this->pdo;
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die('Database connection failed');
        }
    }

    public function getPDO() {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }
}

// Create global database instance
$db = new Database();
$pdo = $db->connect();
?>
