<?php
// classes/AuthService.php

require_once 'Database.php';
require_once 'User.php';

class AuthService {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Kullanıcı girişini kontrol eder
     */
    public function login($username, $password) {
        $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $user = new User($row['id'], $row['username'], $row['email'], $row['password']);

            if ($user->verifyPassword($password)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Yeni kullanıcıyı veritabanına ekler
     */
    public function register($username, $email, $password) {
        $query = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $this->conn->prepare($query);
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashed);
        return $stmt->execute();
    }

    /**
     * Kullanıcıyı ID ile getirir
     */
    public function getUserById($id) {
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
