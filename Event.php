<?php
// classes/Event.php

require_once 'Database.php';

class Event {
    private $conn;
    private $table = "events";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function create($title, $description, $date) {
        $query = "INSERT INTO " . $this->table . " (title, description, event_date) VALUES (:title, :description, :date)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":date", $date);
        return $stmt->execute();
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY event_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}
