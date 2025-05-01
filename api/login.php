<?php
header("Content-Type: application/json");

require_once '../classes/AuthService.php';
require_once '../classes/JWTService.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Kullanıcı adı ve şifre gereklidir."]);
    exit;
}

$authService = new AuthService();
$user = $authService->login($data['username'], $data['password']);

if ($user) {
    $jwt = new JWTService();
    $token = $jwt->generateToken([
        "id" => $user->getId(),
        "username" => $user->getUsername(),
        "email" => $user->getEmail()
    ]);

    echo json_encode([
        "token" => $token,
        "user" => [
            "id" => $user->getId(),
            "username" => $user->getUsername(),
            "email" => $user->getEmail()
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Geçersiz kullanıcı adı veya şifre."]);
}
