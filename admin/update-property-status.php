<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once '../auth.php';
require_once '../connect.php';

$admin = requireAdmin();
$data = json_decode(file_get_contents("php://input"), true);

$propertyId = $data['propertyId'] ?? null;
$action = $data['status'] ?? '';

if ($action === 'approved' && $propertyId) {
  $stmt = $con->prepare("UPDATE Property SET status = 'Available' WHERE propertyId = ?");
  $stmt->bind_param("i", $propertyId);

  if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Property is now live!"]);
  } else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $con->error]);
  }
} else {
  http_response_code(400);
  echo json_encode(["status" => "fail", "message" => "Invalid request"]);
}
