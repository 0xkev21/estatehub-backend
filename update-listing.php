<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require 'connect.php';
require 'auth.php';

$userPayload = requirePaidMember();
$memberId = $userPayload->id ?? 0;
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($memberId == 0 || $propertyId == 0) {
  http_response_code(400);
  echo json_encode(["status" => "fail", "message" => "Invalid request."]);
  exit;
}

// 1. Ownership & Data Retrieval
$checkStmt = $con->prepare("SELECT locationId, memberId FROM Property WHERE propertyId = ?");
$checkStmt->bind_param("i", $propertyId);
$checkStmt->execute();
$propData = $checkStmt->get_result()->fetch_assoc();

if (!$propData || $propData['memberId'] != $memberId) {
  http_response_code(403);
  echo json_encode(["status" => "fail", "message" => "Unauthorized access."]);
  exit;
}

$locationId = $propData['locationId'];

// 2. Sanitize Inputs
$title = htmlspecialchars(strip_tags($_POST['title']));
$description = htmlspecialchars(strip_tags($_POST['description'] ?? ''));
$price = (int)$_POST['price'];
$area = (int)$_POST['area'];
$bedrooms = (int)$_POST['bedrooms'];
$bathrooms = (int)$_POST['bathrooms'];
$status = $_POST['status'] ?? 'Available';
$townshipId = (int)$_POST['townshipId'];
$propertyTypeId = (int)$_POST['propertytypeId'];
$listingTypeId = (int)$_POST['listingtypeId'];
$latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;

$con->begin_transaction();

// file_put_contents('debug.txt', print_r($_POST, true));

try {
  // 3. Update Location
  $stmtLoc = $con->prepare("UPDATE PropertyLocation SET latitude = ?, longitude = ?, townshipId = ? WHERE locationId = ?");
  $stmtLoc->bind_param("ddii", $latitude, $longitude, $townshipId, $locationId);
  $stmtLoc->execute();

  // 4. Update Property
  $sqlProp = "UPDATE Property SET 
                title = ?, description = ?, price = ?, area = ?, 
                bedrooms = ?, bathrooms = ?, status = ?, 
                propertyTypeid = ?, listingTypeid = ? 
                WHERE propertyId = ? AND memberId = ?";
  $stmtProp = $con->prepare($sqlProp);
  $stmtProp->bind_param("ssiiiiisiii", $title, $description, $price, $area, $bedrooms, $bathrooms, $status, $propertyTypeId, $listingTypeId, $propertyId, $memberId);
  $stmtProp->execute();

  // 5. Manage Images (Delete removed ones)
  if (isset($_POST['keepImages'])) {
    $keepImages = json_decode($_POST['keepImages'], true); // Array of paths to keep

    // Find images in DB NOT in keep list
    $imgQuery = $con->prepare("SELECT imagePath FROM PropertyImage WHERE propertyId = ?");
    $imgQuery->bind_param("i", $propertyId);
    $imgQuery->execute();
    $dbImages = $imgQuery->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($dbImages as $dbImg) {
      if (!in_array($dbImg['imagePath'], $keepImages)) {
        // Delete physical file
        if (file_exists($dbImg['imagePath'])) {
          unlink($dbImg['imagePath']);
        }
        // Delete DB record
        $delImg = $con->prepare("DELETE FROM PropertyImage WHERE propertyId = ? AND imagePath = ?");
        $delImg->bind_param("is", $propertyId, $dbImg['imagePath']);
        $delImg->execute();
      }
    }
  }

  // 6. Upload New Images
  if (!empty($_FILES['images']['name'][0])) {
    $targetDir = "uploads/";
    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
      if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
        $newFileName = $propertyId . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $targetDir . $newFileName;

        if (move_uploaded_file($tmpName, $targetPath)) {
          $stmtInsImg = $con->prepare("INSERT INTO PropertyImage (propertyId, imagePath) VALUES (?, ?)");
          $stmtInsImg->bind_param("is", $propertyId, $targetPath);
          $stmtInsImg->execute();
        }
      }
    }
  }

  $con->commit();
  echo json_encode(["status" => "success", "message" => "Listing updated successfully."]);
} catch (Exception $e) {
  $con->rollback();
  http_response_code(500);
  echo json_encode(["status" => "fail", "message" => $e->getMessage()]);
}
