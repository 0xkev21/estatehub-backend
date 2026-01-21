<?php
require_once '../auth.php';

$admin = requireAdmin();

$data = json_decode(file_get_contents("php://input"));

if (isset($data->propertyId) && isset($data->status)) {
    $propertyId = intval($data->propertyId);
    $status = $data->status;
    
    $validStatuses = ['Pending', 'Available', 'Rejected', 'Sold'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(["status" => "error", "message" => "Invalid status value."]);
        exit;
    }

    $stmt = $con->prepare("UPDATE property SET status = ? WHERE propertyId = ?");
    $stmt->bind_param("si", $status, $propertyId);

    if ($stmt->execute()) {
        $logMsg = "Admin updated Property ID $propertyId to status: $status";
        $con->query("INSERT INTO activity_logs (description, logDate) VALUES ('$logMsg', NOW())");
        
        echo json_encode(["status" => "success", "message" => "Status updated to $status"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing required data."]);
}