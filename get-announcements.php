<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
require 'connect.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

try {
  $sql = "SELECT announcementId, title, description, announcement, date, announcementImage 
            FROM announcement 
            ORDER BY date DESC LIMIT ?";

  $stmt = $con->prepare($sql);
  $stmt->bind_param("i", $limit);
  $stmt->execute();
  $result = $stmt->get_result();
  $data = $result->fetch_all(MYSQLI_ASSOC);

  echo json_encode(["status" => "success", "data" => $data]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
