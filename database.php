<?php
/**
 * Database Connection Handler (uses config.php constants)
 * Uses PDO for secure database connections
 */

require_once __DIR__ . '/config.php';

class Database {
    private $host;
    private $db_name;
    private $db_user;
    private $db_pass;
    private $db_port;
    private $pdo;

    public function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->db_user = DB_USER;
        $this->db_pass = DB_PASS;
        $this->db_port = DB_PORT;
    }

    public function connect() {
        $this->pdo = null;

        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $this->host, (int)$this->db_port, $this->db_name);
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO($dsn, $this->db_user, $this->db_pass, $options);

            return $this->pdo;
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            if (defined('APP_ENV') && APP_ENV === 'development') {
                die('Database connection failed: ' . $e->getMessage());
            } else {
                die('Database connection failed');
            }
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