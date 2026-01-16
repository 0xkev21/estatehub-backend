<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once 'connect.php';

// Get visitor IP
$ip = $_SERVER['REMOTE_ADDR'];
$currentMonth = date('m');
$currentYear = date('Y');

// Check if this IP already visited this month
$stmt = $con->prepare("SELECT visitorId FROM SiteVisitors WHERE ipAddress = ? AND MONTH(visitDate) = ? AND YEAR(visitDate) = ?");
$stmt->bind_param("sss", $ip, $currentMonth, $currentYear);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  $ins = $con->prepare("INSERT INTO SiteVisitors (ipAddress) VALUES (?)");
  $ins->bind_param("s", $ip);
  $ins->execute();
}

// Return the total count for the current month
$countRes = $con->query("SELECT COUNT(*) as total FROM SiteVisitors WHERE MONTH(visitDate) = '$currentMonth'");
echo json_encode($countRes->fetch_assoc());
