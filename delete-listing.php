<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require 'connect.php';
require 'auth.php';

// 1. Authenticate and get user ID
$userPayload = requireAuth();
$memberId = $userPayload->id ?? 0;
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($memberId == 0 || $propertyId == 0) {
  http_response_code(400);
  echo json_encode(["status" => "fail", "message" => "Invalid request."]);
  exit;
}

// 2. Verify Ownership and Get Location ID / Image Paths
$checkSql = "SELECT locationId, memberId FROM Property WHERE propertyId = ?";
$stmtCheck = $con->prepare($checkSql);
$stmtCheck->bind_param("i", $propertyId);
$stmtCheck->execute();
$propData = $stmtCheck->get_result()->fetch_assoc();

if (!$propData || $propData['memberId'] != $memberId) {
  http_response_code(403);
  echo json_encode(["status" => "fail", "message" => "Unauthorized: You do not own this listing."]);
  exit;
}

$locationId = $propData['locationId'];

// 3. Start Transaction
$con->begin_transaction();

try {
  // A. Fetch and Delete Physical Images
  $stmtImgs = $con->prepare("SELECT imagePath FROM PropertyImage WHERE propertyId = ?");
  $stmtImgs->bind_param("i", $propertyId);
  $stmtImgs->execute();
  $images = $stmtImgs->get_result();

  while ($row = $images->fetch_assoc()) {
    $filePath = $row['imagePath'];
    if (file_exists($filePath)) {
      unlink($filePath); // Delete the file from the server
    }
  }

  // B. Delete Database Records (Child tables first)
  $con->query("DELETE FROM PropertyImage WHERE propertyId = $propertyId");

  // C. Delete the Property
  $con->query("DELETE FROM Property WHERE propertyId = $propertyId");

  // D. Delete the Location
  if ($locationId) {
    $con->query("DELETE FROM PropertyLocation WHERE locationId = $locationId");
  }

  $con->commit();
  echo json_encode(["status" => "success", "message" => "Listing and associated data deleted successfully."]);
} catch (Exception $e) {
  $con->rollback();
  http_response_code(500);
  echo json_encode(["status" => "fail", "message" => "Delete Error: " . $e->getMessage()]);
}
