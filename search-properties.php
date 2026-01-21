<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require 'connect.php';

$keyword = $_GET['keyword'] ?? '';
$listingTypeId = $_GET['listingTypeId'] ?? '';
$propertyTypeId = $_GET['propertyTypeId'] ?? '';
$regionId = $_GET['regionId'] ?? '';
$districtId = $_GET['districtId'] ?? '';
$townshipId = $_GET['townshipId'] ?? '';
$minPrice = $_GET['minPrice'] ?? 0;
$maxPrice = $_GET['maxPrice'] ?? 10000000000;

try {
    $sql = "SELECT p.propertyId, p.title, p.price, p.bedrooms, p.bathrooms, p.area, p.viewCount, d.district, t.township, lt.listingtype,
            (SELECT imagePath FROM propertyimage pi WHERE p.propertyId = pi.propertyId LIMIT 1) as thumbnail
            FROM Property p
            JOIN propertylocation l ON l.locationId = p.locationId
            JOIN township t ON t.townshipId = l.townshipId
            JOIN district d ON d.districtId = t.districtId
            JOIN region r ON r.regionId = d.regionId
            JOIN listingtype lt ON lt.listingTypeId = p.listingTypeId
            WHERE p.status = 'Available'
            AND p.price BETWEEN ? AND ?";

    $params = [$minPrice, $maxPrice];
    $types = "dd";

    if (!empty($keyword)) {
        $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
        $searchTerm = "%$keyword%";
        array_push($params, $searchTerm, $searchTerm);
        $types .= "ss";
    }

    if (!empty($listingTypeId)) {
        $sql .= " AND p.listingTypeId = ?";
        $params[] = $listingTypeId;
        $types .= "i";
    }
    if (!empty($propertyTypeId)) {
        $sql .= " AND p.propertyTypeId = ?";
        $params[] = $propertyTypeId;
        $types .= "i";
    }

    if (!empty($townshipId)) {
        $sql .= " AND t.townshipId = ?";
        $params[] = $townshipId;
        $types .= "i";
    } elseif (!empty($districtId)) {
        $sql .= " AND d.districtId = ?";
        $params[] = $districtId;
        $types .= "i";
    } elseif (!empty($regionId)) {
        $sql .= " AND r.regionId = ?";
        $params[] = $regionId;
        $types .= "i";
    }

    $sql .= " GROUP BY p.propertyId ORDER BY p.listedDate DESC";

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
