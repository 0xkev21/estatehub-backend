<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'connect.php';
require 'auth.php';

$userPayload = requireAuth();
$memberId = $userPayload->id ?? 0;

if ($memberId == 0) {
    http_response_code(401);
    echo json_encode(["status" => "fail", "message" => "Unauthorized"]);
    exit;
}

try {
    // Fetch Dashboard Statistics
    $statsSql = "SELECT 
        COUNT(CASE WHEN status = 'Available' THEN 1 END) as activeListings,
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pendingListings,
        COUNT(CASE WHEN status = 'Sold' THEN 1 END) as propertiesSold,
        SUM(viewCount) as totalViews
        FROM Property 
        WHERE memberId = ?";

    $stmtStats = $con->prepare($statsSql);
    $stmtStats->bind_param("i", $memberId);
    $stmtStats->execute();
    $stats = $stmtStats->get_result()->fetch_assoc();

    // Fetch Membership Expiry
    $memberSql = "SELECT expireDate FROM Member WHERE memberId = ?";
    $stmtMem = $con->prepare($memberSql);
    $stmtMem->bind_param("i", $memberId);
    $stmtMem->execute();
    $memberData = $stmtMem->get_result()->fetch_assoc();

    // Fetch Recent Inquiries
    $inquirySql = "SELECT i.*, p.title as propertyTitle, pi.imagePath
                FROM Inquiry i
                JOIN Property p ON i.propertyId = p.propertyId
                JOIN propertyimage pi ON p.propertyId = pi.propertyId
                WHERE p.memberId = ?
                group by inquiryId
                ORDER BY i.dateSent DESC LIMIT 5";

    $stmtInq = $con->prepare($inquirySql);
    $stmtInq->bind_param("i", $memberId);
    $stmtInq->execute();
    $inquiries = $stmtInq->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "stats" => [
                "active" => (int)($stats['activeListings'] ?? 0),
                "pending" => (int)($stats['pendingListings'] ?? 0),
                "sold" => (int)($stats['propertiesSold'] ?? 0),
                "views" => (int)($stats['totalViews'] ?? 0)
            ],
            "membershipExpiry" => $memberData['expireDate'] ?? 'N/A',
            "recentInquiries" => $inquiries
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$con->close();
