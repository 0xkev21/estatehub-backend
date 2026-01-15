<?php
require_once '../auth.php';
require_once '../connect.php';

$user = requireAdmin();
$adminId = $user->id;

$data = json_decode(file_get_contents("php://input"), true);
$oldPassword = $data['oldPassword'] ?? '';
$newPassword = $data['newPassword'] ?? '';

if (empty($oldPassword) || empty($newPassword)) {
  echo json_encode(["status" => "error", "message" => "All fields are required"]);
  exit;
}

try {
  // 1. Fetch current hashed password from admin table
  $stmt = $con->prepare("SELECT password FROM admin WHERE adminId = ?");
  $stmt->bind_param("i", $adminId);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();

  if (!$result || !password_verify($oldPassword, $result['password'])) {
    echo json_encode(["status" => "error", "message" => "Incorrect current password"]);
    exit;
  }

  // 2. Hash and update the new password
  $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
  $updateStmt = $con->prepare("UPDATE admin SET password = ? WHERE adminId = ?");
  $updateStmt->bind_param("si", $hashedPassword, $adminId);

  if ($updateStmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Password updated successfully"]);
  } else {
    throw new Exception("Failed to update password");
  }
} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
