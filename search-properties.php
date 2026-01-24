<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require 'connect.php';

// Pagination Parameters
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 4;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter Parameters
$keyword = $_GET['keyword'] ?? '';
$listingTypeId = $_GET['listingTypeId'] ?? '';
$propertyTypeId = $_GET['propertyTypeId'] ?? '';
$regionId = $_GET['regionId'] ?? '';
$districtId = $_GET['districtId'] ?? '';
$townshipId = $_GET['townshipId'] ?? '';
$minPrice = $_GET['minPrice'] ?? 0;
$maxPrice = $_GET['maxPrice'] ?? 10000000000;

try {
    // Base WHERE clause
    $whereClauses = "WHERE p.status = 'Available' AND p.price BETWEEN ? AND ?";
    $params = [$minPrice, $maxPrice];
    $types = "dd";

    if (!empty($keyword)) {
        $whereClauses .= " AND (p.title LIKE ? OR p.description LIKE ?)";
        $searchTerm = "%$keyword%";
        array_push($params, $searchTerm, $searchTerm);
        $types .= "ss";
    }

    if (!empty($listingTypeId)) {
        $whereClauses .= " AND p.listingTypeId = ?";
        $params[] = $listingTypeId;
        $types .= "i";
    }

    if (!empty($propertyTypeId)) {
        $whereClauses .= " AND p.propertyTypeId = ?";
        $params[] = $propertyTypeId;
        $types .= "i";
    }

    if (!empty($townshipId)) {
        $whereClauses .= " AND t.townshipId = ?";
        $params[] = $townshipId;
        $types .= "i";
    } elseif (!empty($districtId)) {
        $whereClauses .= " AND d.districtId = ?";
        $params[] = $districtId;
        $types .= "i";
    } elseif (!empty($regionId)) {
        $whereClauses .= " AND r.regionId = ?";
        $params[] = $regionId;
        $types .= "i";
    }

    // --- COUNT QUERY ---
    $countSql = "SELECT COUNT(DISTINCT p.propertyId) as total 
                FROM Property p 
                JOIN propertylocation l ON l.locationId = p.locationId
                JOIN township t ON t.townshipId = l.townshipId
                JOIN district d ON d.districtId = t.districtId
                JOIN region r ON r.regionId = d.regionId
                $whereClauses";

    $countStmt = $con->prepare($countSql);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $totalResult = $countStmt->get_result()->fetch_assoc();
    $totalItems = $totalResult['total'];
    $totalPages = ceil($totalItems / $limit);

    // --- DATA QUERY ---
    $sql = "SELECT p.propertyId, p.title, p.price, p.bedrooms, p.bathrooms, p.area, p.viewCount, d.district, t.township, lt.listingtype,
            (SELECT imagePath FROM propertyimage pi WHERE p.propertyId = pi.propertyId LIMIT 1) as thumbnail
            FROM Property p
            JOIN propertylocation l ON l.locationId = p.locationId
            JOIN township t ON t.townshipId = l.townshipId
            JOIN district d ON d.districtId = t.districtId
            JOIN region r ON r.regionId = d.regionId
            JOIN listingtype lt ON lt.listingTypeId = p.listingTypeId
            $whereClauses
            GROUP BY p.propertyId 
            ORDER BY p.listedDate DESC 
            LIMIT ? OFFSET ?";

    // Add pagination params
    $dataParams = $params;
    $dataParams[] = $limit;
    $dataParams[] = $offset;
    $dataTypes = $types . "ii";

    $stmt = $con->prepare($sql);
    $stmt->bind_param($dataTypes, ...$dataParams);
    $stmt->execute();
    $properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $properties,
        "pagination" => [
            "currentPage" => $page,
            "totalPages" => $totalPages,
            "totalItems" => (int)$totalItems
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
