<?php
// classes/JWTService.php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../vendor/autoload.php';

class JWTService {
    private $secret = "gizli_anahtar"; // Güvenli bir anahtar girin

    public function generateToken($payload) {
        $issuedAt = time();
        $expire = $issuedAt + (60 * 60); // 1 saat geçerli
        $payload['iat'] = $issuedAt;
        $payload['exp'] = $expire;

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function validateToken($jwt) {
        try {
            $decoded = JWT::decode($jwt, new Key($this->secret, 'HS256'));
            return (array)$decoded;
        } catch (Exception $e) {
            return null;
        }
    }
}
