<?php
require_once '../auth.php';
require_once '../connect.php';

$admin = requireAdmin();

// Get date filters from React frontend
$startDate = isset($_GET['startDate']) && !empty($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) && !empty($_GET['endDate']) ? $_GET['endDate'] : null;

// Dynamic Date Filter Helper
function dateClause($column, $start, $end, $isAnd = false)
{
  $clause = "";
  if ($start && $end) {
    $clause = "$column BETWEEN '$start' AND '$end'";
  } elseif ($start) {
    $clause = "$column >= '$start'";
  } elseif ($end) {
    $clause = "$column <= '$end'";
  }

  if (empty($clause)) return "";
  return ($isAnd ? " AND " : " WHERE ") . $clause;
}

// --- User & Membership Intelligence ---
$memFilter = dateClause('joinDate', $startDate, $endDate);
$memberStats = $con->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN expireDate >= NOW() THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN expireDate < NOW() THEN 1 ELSE 0 END) as expired
    FROM member $memFilter")->fetch_assoc();

// --- Property Inventory Analytics ---
$propFilter = dateClause('listedDate', $startDate, $endDate);
$propertyStats = $con->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM property $propFilter")->fetch_assoc();

// --- Geographic Distribution (Section 2.2) ---
$geoDist = [];
$geoRes = $con->query("SELECT r.region, COUNT(p.propertyId) as count 
    FROM region r
    LEFT JOIN district d ON r.regionId = d.regionId
    LEFT JOIN township t ON d.districtId = t.districtId
    LEFT JOIN propertylocation pl ON t.townshipId = pl.townshipId
    LEFT JOIN property p ON pl.locationId = p.locationId
    GROUP BY r.regionId");
while ($row = $geoRes->fetch_assoc()) $geoDist[] = $row;

// --- Financial Performance ---
$revFilter = dateClause('pa.approveDate', $startDate, $endDate, true);
$revenueRes = $con->query("SELECT 
    pm.paymentMethodName as method, 
    SUM(mf.amount) as total 
    FROM memberpayment mp
    JOIN paymentmethod pm ON mp.paymentMethodId = pm.paymentMethodId
    JOIN memberfee mf ON mp.memberfeeId = mf.memberfeeId
    JOIN paymentapproval pa ON mp.paymentId = pa.paymentId
    WHERE pa.approveDate IS NOT NULL $revFilter
    GROUP BY pm.paymentMethodId");
$revenueByMethod = [];
while ($row = $revenueRes->fetch_assoc()) $revenueByMethod[] = $row;

$popularListings = [];
$popRes = $con->query("SELECT propertyId, title, viewCount FROM property $propFilter ORDER BY viewCount DESC LIMIT 5");
while ($row = $popRes->fetch_assoc()) $popularListings[] = $row;

$mostInquired = [];
$inqRes = $con->query("SELECT p.propertyId, p.title, COUNT(i.inquiryId) as inquiryCount 
    FROM property p
    JOIN inquiry i ON p.propertyId = i.propertyId
    $propFilter
    GROUP BY p.propertyId 
    ORDER BY inquiryCount DESC 
    LIMIT 5");
while ($row = $inqRes->fetch_assoc()) $mostInquired[] = $row;

$inquiryStats = $con->query("SELECT COUNT(*) as total FROM inquiry " . dateClause('dateSent', $startDate, $endDate))->fetch_assoc();

$monthlyMembers = [];
$memRes = $con->query("SELECT DATE_FORMAT(joinDate, '%b %Y') as month, COUNT(*) as count 
    FROM member $memFilter GROUP BY YEAR(joinDate), MONTH(joinDate) ORDER BY joinDate ASC");
while ($row = $memRes->fetch_assoc()) $monthlyMembers[] = $row;

$monthlyListings = [];
$listRes = $con->query("SELECT DATE_FORMAT(listedDate, '%b %Y') as month, COUNT(*) as count 
    FROM property $propFilter GROUP BY YEAR(listedDate), MONTH(listedDate) ORDER BY listedDate ASC");
while ($row = $listRes->fetch_assoc()) $monthlyListings[] = $row;

// Final Response Construction
echo json_encode([
  "summary" => [
    "members" => $memberStats,
    "properties" => $propertyStats,
    "inquiries" => $inquiryStats['total'],
    "revenue" => array_sum(array_column($revenueByMethod, 'total'))
  ],
  "geo" => $geoDist,
  "revenueByMethod" => $revenueByMethod,
  "popular" => $popularListings,
  "mostInquired" => $mostInquired,
  "charts" => [
    "members" => $monthlyMembers,
    "listings" => $monthlyListings
  ]
]);
