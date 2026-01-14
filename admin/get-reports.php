<?php
require_once '../auth.php';
require_once '../connect.php';

$admin = requireAdmin(); //

// 1. Must Have: Lifetime Totals
$memberCount = $con->query("SELECT COUNT(*) as total FROM member")->fetch_assoc()['total'];
$propertyCount = $con->query("SELECT COUNT(*) as total FROM property")->fetch_assoc()['total'];

$revenue = $con->query("SELECT SUM(amount) as total FROM memberpayment mp
join memberfee mf on mf.memberfeeId = mp.memberfeeId
join paymentapproval pa on pa.paymentId = mp.paymentId
where approveDate IS NOT NULL")->fetch_assoc()['total'];

// 2. Should Have: Monthly New Members (Last 6 Months)
$monthlyMembers = [];
$memRes = $con->query("SELECT DATE_FORMAT(joinDate, '%b') as month, COUNT(*) as count 
                       FROM member 
                       WHERE joinDate >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                       GROUP BY MONTH(joinDate) ORDER BY joinDate ASC");
while ($row = $memRes->fetch_assoc()) $monthlyMembers[] = $row;

// 3. Should Have: Monthly New Listings (Last 6 Months)
$monthlyListings = [];
$listRes = $con->query("SELECT DATE_FORMAT(listedDate, '%b') as month, COUNT(*) as count 
                        FROM property 
                        WHERE listedDate >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                        GROUP BY MONTH(listedDate) ORDER BY listedDate ASC");
while ($row = $listRes->fetch_assoc()) $monthlyListings[] = $row;

echo json_encode([
  "summary" => [
    "members" => $memberCount,
    "properties" => $propertyCount,
    "revenue" => $revenue ?? 0
  ],
  "charts" => [
    "members" => $monthlyMembers,
    "listings" => $monthlyListings
  ]
]);
