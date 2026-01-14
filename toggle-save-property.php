<?php
require_once 'auth.php';
require_once 'connect.php';

$user = requireAuth(); // Returns an object, not an array

// CHANGE THIS LINE: Access property using -> instead of ['']
$memberId = $user->id;

$data = json_decode(file_get_contents("php://input"), true);
$propertyId = $data['propertyId'] ?? null;

if (!$propertyId) {
  echo json_encode(["status" => "error", "message" => "No property ID provided"]);
  exit;
}

// Check if already saved
$check = $con->prepare("SELECT propertySavedId FROM propertysaved WHERE memberId = ? AND propertyId = ?");
$check->bind_param("ii", $memberId, $propertyId);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
  // Remove if already exists
  $stmt = $con->prepare("DELETE FROM propertysaved WHERE memberId = ? AND propertyId = ?");
  $stmt->bind_param("ii", $memberId, $propertyId);
  $stmt->execute();
  echo json_encode(["status" => "success", "action" => "removed"]);
} else {
  // Add if it doesn't exist
  $stmt = $con->prepare("INSERT INTO propertysaved (memberId, propertyId, savedDate) VALUES (?, ?, CURRENT_DATE)");
  $stmt->bind_param("ii", $memberId, $propertyId);
  $stmt->execute();
  echo json_encode(["status" => "success", "action" => "saved"]);
}
