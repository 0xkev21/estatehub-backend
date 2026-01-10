<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
require 'connect.php';

try {
    // Select fields based on updated PaymentMethod class
    $query = "SELECT paymentMethodId, paymentMethodName, paymentDescription, paymentMethodImage, paymentNumber FROM PaymentMethod";
    $result = $con->query($query);
    
    $methods = [];
    while ($row = $result->fetch_assoc()) {
        $methods[] = $row;
    }

    echo json_encode(["status" => "success", "data" => $methods]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>