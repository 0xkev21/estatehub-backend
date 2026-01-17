<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require 'connect.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID is required"]);
    exit;
}

try {
    $stmt = $con->prepare("SELECT * FROM announcement WHERE announcementId = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        echo json_encode(["status" => "success", "data" => $data]);
    } else {
        echo json_encode(["status" => "error", "message" => "Announcement not found"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
