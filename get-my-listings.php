<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'connect.php';
require 'auth.php';

$userPayload = requireAuth();
$memberId = $userPayload->id ?? 0;

try {
    $sql = "SELECT 
                p.propertyId, 
                p.title, 
                p.status, 
                p.viewCount, 
                p.listedDate,
                (SELECT imagePath FROM PropertyImage WHERE propertyId = p.propertyId LIMIT 1) as thumbnail,
                COUNT(i.inquiryId) as inquiryCount
            FROM Property p
            LEFT JOIN Inquiry i ON p.propertyId = i.propertyId
            WHERE p.memberId = ?
            GROUP BY p.propertyId
            ORDER BY p.listedDate DESC";

    $stmt = $con->prepare($sql);

    if (!$stmt) {
        throw new Exception("SQL Prepare Failed: " . $con->error);
    }

    $stmt->bind_param("i", $memberId);
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