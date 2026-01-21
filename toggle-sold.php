<?php

require 'auth.php';

$user = requireAuth();
$memberId = $user->id;

$propertyId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$newStatus = isset($_GET['status']) ? $_GET['status'] : '';

if (!in_array($newStatus, ['Available', 'Sold'])) {
  echo json_encode(["status" => "error", "message" => "Invalid status request."]);
  exit;
}

// Verify Ownership
$check = $con->prepare("SELECT propertyId FROM property WHERE propertyId = ? AND memberId = ?");
$check->bind_param("ii", $propertyId, $memberId);
$check->execute();
if ($check->get_result()->num_rows === 0) {
  echo json_encode(["status" => "error", "message" => "Unauthorized."]);
  exit;
}

// Perform Update
$update = $con->prepare("UPDATE property SET status = ? WHERE propertyId = ?");
$update->bind_param("si", $newStatus, $propertyId);

if ($update->execute()) {
  echo json_encode(["status" => "success", "message" => "Status updated to $newStatus."]);
} else {
  echo json_encode(["status" => "error", "message" => "Database error."]);
}
