<?php
require 'auth.php'; // This already includes connect.php and JWT logic

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
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Property not found"]);
    exit;
  }

  // 2. SOFT AUTH: Check if the user is logged in WITHOUT killing the script
  $token = getBearerToken(); // Function from your auth.php
  $currentUser = null;
  $isSaved = false;

  if ($token) {
    try {
      // Reuse the logic from your requireAuth()
      $currentUser = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($_ENV['JWT_KEY'], 'HS256'));
      $saveStmt = $con->prepare("SELECT 1 FROM propertysaved WHERE memberId = ? AND propertyId = ?");
      $saveStmt->bind_param("ii", $currentUser->id, $id);
      $saveStmt->execute();
      $isSaved = $saveStmt->get_result()->num_rows > 0;
    } catch (Exception $e) {
      // Token is invalid/expired, treat as guest
      $currentUser = null;
    }
  }

  // 3. SECURITY LOGIC
  $isApproved = ($property['status'] == 'Available');
  $isOwner = ($currentUser && $currentUser->id == $property['memberId']);
  $isAdmin = ($currentUser && isset($currentUser->role) && $currentUser->role === 'admin');

  // If it's NOT approved, only the Owner or Admin can proceed
  if (!$isApproved && !$isOwner && !$isAdmin) {
    http_response_code(403);
    echo json_encode([
      "status" => "error",
      "message" => "This property is pending approval and is not publicly visible."
    ]);
    exit;
  }

  $imgStmt = $con->prepare("SELECT imagePath FROM PropertyImage WHERE propertyId = ?");
  $imgStmt->bind_param("i", $id);
  $imgStmt->execute();
  $images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

  echo json_encode([
    "status" => "success",
    "data" => $property,
    "images" => array_column($images, 'imagePath'),
    "isPreview" => ($property['status'] == 'Pending'),
    "isSaved" => $isSaved
  ]);
} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
