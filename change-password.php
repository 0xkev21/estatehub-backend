<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require 'connect.php';
require 'auth.php';

$userPayload = requireAuth();
$memberId = $userPayload->id ?? 0;

$input = json_decode(file_get_contents("php://input"), true);

$currentPassword = $input['currentPassword'] ?? '';
$newPassword = $input['newPassword'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
  http_response_code(400);
  echo json_encode(["status" => "fail", "message" => "All fields are required."]);
  exit;
}

try {
  // Fetch the current hashed password from the Member table
  $stmt = $con->prepare("SELECT password FROM Member WHERE memberId = ?");
  $stmt->bind_param("i", $memberId);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();

  if (!$result) {
    throw new Exception("Member not found.");
  }

  // 3. Verify current password
  if (!password_verify($currentPassword, $result['password'])) {
    http_response_code(401);
    echo json_encode(["status" => "fail", "message" => "Incorrect current password."]);
    exit;
  }

  // 4. Hash and update the new password
  $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
  $updateStmt = $con->prepare("UPDATE Member SET password = ? WHERE memberId = ?");
  $updateStmt->bind_param("si", $newHashedPassword, $memberId);

  if ($updateStmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Password updated successfully."]);
  } else {
    throw new Exception("Failed to update password.");
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
