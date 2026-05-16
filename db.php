<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $host = "localhost";
        $db_name = "pollaio_iot";
        $username = "root";
        $password_db = "";

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$db_name;
            charset=utf8", $username, $password_db);
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