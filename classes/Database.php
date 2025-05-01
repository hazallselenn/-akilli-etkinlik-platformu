<?php
// classes/Database.php

class Database {
    private $host = "localhost";
    private $db_name = "veritabani_adi";
    private $username = "root";
    private $password = "";
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                  $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Bağlantı hatası: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
