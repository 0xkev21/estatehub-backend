<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

header("Content-Type: application/json");

require 'connect.php';

$id = $_GET['id'] ?? null;

if (!$id) {
  echo json_encode(["status" => "error", "message" => "No ID provided"]);
  exit;
}

try {
  $stmt = $con->prepare("SELECT p.*, t.township, t.townshipId, 
                          d.district, d.districtId, 
                          r.region, r.regionId, 
                          pt.propertyType, lt.listingType, 
                          pt.propertyTypeId, lt.listingTypeId,
                          l.latitude, l.longitude
                          FROM property p
                          JOIN propertyLocation l ON p.locationId = l.locationId
                          JOIN township t ON l.townshipId = t.townshipId
                          JOIN district d ON t.districtId = d.districtId
                          JOIN region r ON d.regionId = r.regionId
                          JOIN propertyType pt ON p.propertyTypeid = pt.propertyTypeId
                          JOIN listingType lt ON p.listingTypeid = lt.listingTypeId
                          WHERE p.propertyId = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $property = $stmt->get_result()->fetch_assoc();

  if (!$property) {
    echo json_encode(["status" => "error", "message" => "Property not found"]);
    exit;
  }

  $imgStmt = $con->prepare("SELECT imagePath FROM PropertyImage WHERE propertyId = ?");
  $imgStmt->bind_param("i", $id);
  $imgStmt->execute();
  $images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

  echo json_encode([
    "status" => "success",
    "data" => $property,
    "images" => array_column($images, 'imagePath')
  ]);
} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
