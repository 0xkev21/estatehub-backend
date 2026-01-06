<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");

require 'connect.php';

$keyword = $_GET['keyword'] ?? '';
$listingTypeId = $_GET['listingTypeId'] ?? '';
$propertyTypeId = $_GET['propertyTypeId'] ?? '';
$stateId = $_GET['stateId'] ?? '';
$townshipId = $_GET['townshipId'] ?? '';
$minPrice = $_GET['minPrice'] ?? 0;
$maxPrice = $_GET['maxPrice'] ?? 10000000000;

try {
  $sql = "SELECT p.propertyId, p.title, p.price, d.district, t.township, listingtype,
            (SELECT imagePath FROM propertyimage pi where p.propertyId = pi.propertyId LIMIT 1) as thumbnail
            FROM Property p
            join propertyimage pi on p.propertyId = pi.propertyId
            join propertylocation l on l.locationId = p.locationId
            join township t on t.townshipId = l.townshipId
            join district d on d.districtId = t.districtId
            join listingtype on listingtype.listingtypeId = p.listingtypeId
            WHERE p.status = 'Available'
          AND p.price BETWEEN ? AND ?";

  $params = [$minPrice, $maxPrice];
  $types = "dd"; // decimal, decimal

  // Dynamic Keyword Search
  if (!empty($keyword)) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%$keyword%";
    array_push($params, $searchTerm, $searchTerm);
    $types .= "ss";
  }

  // Dynamic ID Filters
  if (!empty($listingTypeId)) {
    $sql .= " AND p.listingTypeid = ?";
    $params[] = $listingTypeId;
    $types .= "i";
  }
  if (!empty($propertyTypeId)) {
    $sql .= " AND p.propertyTypeid = ?";
    $params[] = $propertyTypeId;
    $types .= "i";
  }
  if (!empty($townshipId)) {
    $sql .= " AND t.townshipId = ?";
    $params[] = $townshipId;
    $types .= "i";
  } elseif (!empty($stateId)) {
    $sql .= " AND s.stateId = ?";
    $params[] = $stateId;
    $types .= "i";
  }

  $sql .= " GROUP BY propertyId
          ORDER BY listedDate DESC;";

  $stmt = $con->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();
  $properties = $result->fetch_all(MYSQLI_ASSOC);

  echo json_encode(["status" => "success", "data" => $properties]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
