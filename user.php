<?php
header("Content-Type: application/json");

// Gerekli sınıfları yükle
require_once '../classes/User.php';
require_once '../classes/JWTService.php';

// JWT doğrulama
$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? '';
$token = str_replace("Bearer ", "", $authHeader);

$jwtService = new JWTService();
$decoded = $jwtService->validateToken($token);

// Token geçersizse 401 dön
if (!$decoded) {
    http_response_code(401);
    echo json_encode(["error" => "Geçersiz veya eksik token"]);
    exit;
}


$method = $_SERVER['REQUEST_METHOD'];


