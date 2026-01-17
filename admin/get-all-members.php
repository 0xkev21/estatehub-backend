<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

require_once '../auth.php';
require_once '../connect.php';

$admin = requireAdmin();

$sql = "SELECT 
            memberId, 
            firstName, 
            lastName, 
            email, 
            expireDate,
            CASE 
                WHEN expireDate IS NULL THEN 'No Plan'
                WHEN expireDate >= CURDATE() THEN 'Active'
                ELSE 'Expired'
            END as membershipStatus
        FROM Member
        ORDER BY memberId DESC";

$result = $con->query($sql);
$members = [];

while ($row = $result->fetch_assoc()) {
  $members[] = $row;
}

echo json_encode($members);
