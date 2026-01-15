<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'connect.php';
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\KEY;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$data = json_decode(file_get_contents("php://input"));

// Validation Check
if (!$data || !isset($data->email) || !isset($data->password)) {
  http_response_code(400);
  echo json_encode(["status" => "fail", "message" => "Invalid input."]);
  exit;
}

$email = $data->email;
$password = $data->password;

$stmt = $con->prepare("SELECT memberId, firstName, lastName, email, password FROM Member WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  if (password_verify($password, $row["password"])) {
    $payload = [
      "id" => $row["memberId"],
      "firstName" => $row["firstName"],
      "lastName" => $row["lastName"],
      "email" => $row["email"],
      "exp" => time() + (60 * 60 * 24 * 7)
    ];

    $token = JWT::encode($payload, $_ENV["JWT_KEY"], 'HS256');

    echo json_encode([
      "status" => "success",
      "message" => "Login Success.",
      "token" => $token
    ]);
  } else {
    echo json_encode(["status" => "fail", "message" => "Incorrect Email or Password"]);
  }
} else {
  echo json_encode(["status" => "fail", "message" => "Account not found"]);
}
