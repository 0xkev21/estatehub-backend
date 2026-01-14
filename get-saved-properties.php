<?php
require_once 'auth.php';
require_once 'connect.php';

$user = requireAuth(); // Protect the route
$memberId = $user->id; // Use object syntax to avoid the fatal error

try {
  // Join propertysaved with property to get listing details
  $stmt = $con->prepare("
        SELECT p.propertyId, p.title, p.price, d.district, t.township, listingtype,
            (SELECT imagePath FROM propertyimage pi where p.propertyId = pi.propertyId LIMIT 1) as thumbnail
            FROM Property p
            join propertylocation l on l.locationId = p.locationId
            join township t on t.townshipId = l.townshipId
            join district d on d.districtId = t.districtId
            join listingtype on listingtype.listingtypeId = p.listingtypeId
            JOIN propertysaved s ON p.propertyId = s.propertyId
            WHERE s.memberId = ?
            ORDER BY s.savedDate DESC;
    ");
  $stmt->bind_param("i", $memberId);
  $stmt->execute();
  $result = $stmt->get_result();
  $savedProperties = $result->fetch_all(MYSQLI_ASSOC);

  echo json_encode(["status" => "success", "data" => $savedProperties]);
} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
