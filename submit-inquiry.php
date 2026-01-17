<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents("php://input"), true);

  $propertyId = $data['propertyId'] ?? null;
  $name = $data['senderName'] ?? '';
  $email = $data['senderEmail'] ?? '';
  $phone = $data['senderPhone'] ?? '';
  $message = $data['message'] ?? '';

  if (!$propertyId || !$name || !$email) {
    echo json_encode(["status" => "error", "message" => "Required fields missing."]);
    exit;
  }

  // Insert into the inquiry table
  $stmt = $con->prepare("INSERT INTO inquiry (senderName, senderEmail, senderPhone, message, propertyId) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssi", $name, $email, $phone, $message, $propertyId);

  if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Inquiry sent successfully!"]);
  } else {
    echo json_encode(["status" => "error", "message" => $con->error]);
  }
}
