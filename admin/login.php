<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require '../connect.php';
require '../vendor/autoload.php';

use Firebase\JWT\JWT;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

// 1. Query the Admin table
$stmt = $con->prepare("SELECT adminId, password FROM Admin WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// 2. Verify password
if ($admin && password_verify($password, $admin['password'])) {
  $payload = [
    "id" => $admin['adminId'],
    "role" => "admin", // Explicitly set role as admin
    "exp" => time() + (60 * 60 * 24) // 24 hours
  ];

  $jwt = JWT::encode($payload, $_ENV['JWT_KEY'], 'HS256');
  echo json_encode(["status" => "success", "token" => $jwt]);
} else {
  http_response_code(401);
  echo json_encode(["status" => "fail", "message" => "Invalid admin credentials."]);
}
