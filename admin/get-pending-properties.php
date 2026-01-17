<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once '../auth.php';
require_once '../connect.php';

$admin = requireAdmin();

// Fetch properties with 'pending' status
$sql = "SELECT p.propertyId, p.listedDate, p.title, p.price, d.district, t.township, listingtype, firstName, lastName,
            (SELECT imagePath FROM propertyimage pi where p.propertyId = pi.propertyId LIMIT 1) as thumbnail
            FROM Property p
            join propertyimage pi on p.propertyId = pi.propertyId
            join propertylocation l on l.locationId = p.locationId
            join township t on t.townshipId = l.townshipId
            join district d on d.districtId = t.districtId
            join listingtype on listingtype.listingtypeId = p.listingtypeId
            join member on p.memberId = member.memberId
            WHERE p.status = 'Pending'
            GROUP BY propertyId
            ORDER BY listedDate DESC";

$result = $con->query($sql);
$properties = [];

while ($row = $result->fetch_assoc()) {
  $properties[] = $row;
}

echo json_encode($properties);
