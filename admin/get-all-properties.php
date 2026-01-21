<?php
require_once '../auth.php';

$admin = requireAdmin();

$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$startDate = isset($_GET['startDate']) && !empty($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) && !empty($_GET['endDate']) ? $_GET['endDate'] : null;

$sql = "SELECT p.*, m.firstName, m.lastName, t.township, d.district, pt.propertyType
        FROM property p
        JOIN member m ON p.memberId = m.memberId
        JOIN propertylocation pl ON p.locationId = pl.locationId
        JOIN township t ON pl.townshipId = t.townshipId
        JOIN district d ON t.districtId = d.districtId
        JOIN propertytype pt ON p.propertyTypeId = pt.propertyTypeId";

$conditions = [];

if ($status !== 'all') {
  $conditions[] = "p.status = '$status'";
}

if ($startDate && $endDate) {
  $conditions[] = "p.listedDate BETWEEN '$startDate' AND '$endDate'";
}

if (!empty($conditions)) {
  $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY p.listedDate DESC";

$res = $con->query($sql);

$data = [];
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $data[] = $row;
  }
} else {
  error_log("SQL Error: " . $con->error);
}

echo json_encode($data);
