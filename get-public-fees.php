<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  exit;
}
require_once 'connect.php';

try {
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $con->query("SELECT * FROM MemberFee ORDER BY duration ASC");
    echo json_encode(["status" => "success", "data" => $result->fetch_all(MYSQLI_ASSOC)]);
  }
} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
