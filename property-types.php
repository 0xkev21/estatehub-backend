<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  exit;
}

require 'connect.php';

$sql = "SELECT propertyTypeId, propertyType FROM PropertyType";
$result = $con->query($sql);

if ($result) {
  $types = $result->fetch_all(MYSQLI_ASSOC);
  http_response_code(200);
  echo json_encode(["status" => "success", "message" => "PropertyTypes fetched.", "data" => $types]);
} else {
  http_response_code(500);
  echo json_encode(["status" => "fail", "message" => "Failed to fetch property types."]);
}

$con->close();
