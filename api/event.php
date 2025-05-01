
<?php
require_once '../classes/JWTService.php';

$headers = apache_request_headers();
$jwt = str_replace("Bearer ", "", $headers['Authorization'] ?? '');

$jwtService = new JWTService();
$decoded = $jwtService->validateToken($jwt);

if (!$decoded) {
    http_response_code(401);
    echo json_encode(["error" => "Geçersiz veya eksik token"]);
    exit;
}

header("Content-Type: application/json");
require_once '../classes/Event.php';

$eventObj = new Event();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $result = $eventObj->findById($_GET['id']);
        } else {
            $result = $eventObj->readAll();
        }
        echo json_encode($result);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($data['title'], $data['description'], $data['date'])) {
            $created = $eventObj->create($data['title'], $data['description'], $data['date']);
            echo json_encode(["success" => $created]);
        } else {
            echo json_encode(["error" => "Eksik veri"]);
        }
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $data);
        if (isset($data['id'])) {
            $deleted = $eventObj->delete($data['id']);
            echo json_encode(["deleted" => $deleted]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Yöntem desteklenmiyor"]);
        break;
}
