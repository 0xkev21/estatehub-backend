<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'connect.php';

try {
  $sql = "SELECT p.propertyId, p.title, p.price, d.district, t.township, listingtype,
            (SELECT imagePath FROM propertyimage pi where p.propertyId = pi.propertyId LIMIT 1) as thumbnail
            FROM Property p
            join propertylocation l on l.locationId = p.locationId
            join township t on t.townshipId = l.townshipId
            join district d on d.districtId = t.districtId
            join listingtype on listingtype.listingtypeId = p.listingtypeId
            WHERE p.status = 'Available'
            ORDER BY listedDate DESC;";

  $stmt = $con->prepare($sql);

  $stmt->execute();
  $result = $stmt->get_result();
  $listings = $result->fetch_all(MYSQLI_ASSOC);

  echo json_encode([
    "status" => "success",
    "data" => $listings
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["status" => "fail", "message" => $e->getMessage()]);
}

$con->close();
