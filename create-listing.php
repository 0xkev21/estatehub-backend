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
require 'auth.php';

$userPayload = requireAuth();
$memberId = $userPayload->id ?? 0;

if ($memberId == 0) {
  http_response_code(400);
  echo json_encode(["status" => "fail", "message" => "Invalid user token structure."]);
  exit;
}

$requiredFields = ['title', 'price', 'townshipId', 'propertyTypeId', 'listingTypeId'];
foreach ($requiredFields as $field) {
  if (empty($_POST[$field])) {
    http_response_code(400);
    echo json_encode(["status" => "fail", "message" => "Missing field: $field"]);
    exit;
  }
}

$title = htmlspecialchars(strip_tags($_POST['title']));
$description = htmlspecialchars(strip_tags($_POST['description'] ?? ''));
$price = max(0, (int)$_POST['price']);
$area = max(0, (int)($_POST['area'] ?? 0));
$bedrooms = max(0, (int)($_POST['bedrooms'] ?? 0));
$bathrooms = max(0, (int)($_POST['bathrooms'] ?? 0));
$status = "Pending";
$viewCount = 0;
$listedDate = date('Y-m-d');

$townshipId = (int)$_POST['townshipId'];
$propertyTypeId = (int)$_POST['propertyTypeId'];
$listingTypeId = (int)$_POST['listingTypeId'];
$latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;

$con->begin_transaction();

try {
  $stmtLoc = $con->prepare("INSERT INTO PropertyLocation (latitude, longitude, townshipId) VALUES (?, ?, ?)");
  $stmtLoc->bind_param("ddi", $latitude, $longitude, $townshipId);
  $stmtLoc->execute() or throw new Exception("Location Error: " . $stmtLoc->error);
  $newLocationId = $con->insert_id;

  $sqlProp = "INSERT INTO Property 
    (title, description, price, area, bedrooms, bathrooms, status, viewCount, listedDate, memberId, locationId, propertyTypeid, listingTypeid) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $stmtProp = $con->prepare($sqlProp);
  $stmtProp->bind_param("ssiiiisisiiii", $title, $description, $price, $area, $bedrooms, $bathrooms, $status, $viewCount, $listedDate, $memberId, $newLocationId, $propertyTypeId, $listingTypeId);
  $stmtProp->execute() or throw new Exception("Property Error: " . $stmtProp->error);
  $newPropertyId = $con->insert_id;

  $uploadedCount = 0;
  if (!empty($_FILES['images']['name'][0])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $allowTypes = ['image/jpeg', 'image/png', 'image/webp'];

    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
      if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;

      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mimeType = $finfo->file($tmpName);

      if (in_array($mimeType, $allowTypes)) {
        $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
        $newFileName = $newPropertyId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $targetFilePath = $targetDir . $newFileName;

        if (move_uploaded_file($tmpName, $targetFilePath)) {
          $stmtImg = $con->prepare("INSERT INTO PropertyImage (propertyId, imagePath) VALUES (?, ?)");
          $stmtImg->bind_param("is", $newPropertyId, $targetFilePath);
          $stmtImg->execute();
          $uploadedCount++;
        }
      }
    }
  }

  $con->commit();
  http_response_code(201);
  echo json_encode(["status" => "success", "propertyId" => $newPropertyId, "images" => $uploadedCount]);
} catch (Exception $e) {
  $con->rollback();
  if (isset($targetFilePath) && file_exists($targetFilePath)) @unlink($targetFilePath);

  http_response_code(500);
  echo json_encode(["status" => "fail", "message" => $e->getMessage()]);
}
