<?php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $host     = $_ENV['DB_HOST'] ?? 'localhost';
        $db_name  = $_ENV['DB_NAME'] ?? 'pollaio_iot';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Errore di connessione al database: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}