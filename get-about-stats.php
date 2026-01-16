<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once 'connect.php';

try {
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get total approved/available properties
    $propertyRes = $con->query("SELECT COUNT(*) as total FROM Property WHERE status = 'Available'");
    $propertyCount = $propertyRes->fetch_assoc()['total'];

    // Get monthly visitors
    $month = date('m');
    $visitorRes = $con->query("SELECT COUNT(*) as total FROM SiteVisitors WHERE MONTH(visitDate) = '$month'");
    $visitorCount = $visitorRes->fetch_assoc()['total'];

    echo json_encode([
      "status" => "success",
      "listings" => $propertyCount,
      "visitors" => $visitorCount
    ]);
  }
} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
