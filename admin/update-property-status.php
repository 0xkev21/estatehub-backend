<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
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
$newStatus = $data['status'] ?? ''; // 'approved' or 'rejected'

if (!$propertyId || !in_array($newStatus, ['approved', 'rejected'])) {
  http_response_code(400);
  echo json_encode(["status" => "fail", "message" => "Invalid data"]);
  exit;
}

$stmt = $con->prepare("UPDATE Property SET status = ? WHERE propertyId = ?");
$stmt->bind_param("si", $newStatus, $propertyId);

if ($stmt->execute()) {
  echo json_encode(["status" => "success", "message" => "Property $newStatus"]);
} else {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => $con->error]);
}
