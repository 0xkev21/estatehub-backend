<?php
header("Access-Control-Allow-Origin: *");
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

require 'connect.php';

$data = json_decode(file_get_contents("php://input"));

// check data
if (!isset($data->firstName) || !isset($data->email) || !isset($data->password)) {
  http_response_code(400);
  echo json_encode(["status" => "fail", "message" => "Incomplete data. First Name, Email, and Password are required."]);
  exit;
}

$firstName = $data->firstName;
$lastName = $data->lastName;
$email = $data->email;
$password = password_hash($data->password, PASSWORD_BCRYPT);

// Check already registered
$stmt = $con->prepare("select memberId from member where email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  http_response_code(409);
  echo json_encode(["status" => "fail", "message" => "This email address is already registered."]);
  $stmt->close();
  exit;
}
$stmt->close();

$stmt = $con->prepare("insert into member(firstName, lastName, email, password) values(?, ?, ?, ?)");
$stmt->bind_param("ssss", $firstName, $lastName, $email, $password);

$ans = $stmt->execute();
if ($ans) {
  $payload = [
    "id" => $stmt->insert_id,
    "firstName" => $firstName,
    "lastName" => $lastName,
    "email" => $email,
    "exp" => strtotime("+1 week")
  ];
  $token = JWT::encode($payload, $_ENV["JWT_KEY"], 'HS256');
  http_response_code(201);
  echo json_encode(["status" => "success", "message" => "Registration successful. Logging you in.", "token" => $token]);
} else {
  http_response_code(500);
  echo json_encode(["status" => "fail", "message" => "Registration failed. Please try again later."]);
}
$stmt->close();
$con->close();
