<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
require 'connect.php';

try {
    // Select fields based on MemberFee class
    $query = "SELECT memberFeeId, duration, amount, description FROM MemberFee ORDER BY amount ASC";
    $result = $con->query($query);
    
    $fees = [];
    while ($row = $result->fetch_assoc()) {
        $fees[] = $row;
    }

    echo json_encode(["status" => "success", "data" => $fees]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>