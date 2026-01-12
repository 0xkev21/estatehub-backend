<?php
require 'connect.php';
require 'auth.php';

// Authenticate the member
$userPayload = requireAuth();
$memberId = $userPayload->id;

try {
  // Join Inquiry -> Property -> Member to filter by current user
  $sql = "SELECT i.*, p.title as propertyTitle 
            FROM Inquiry i
            JOIN Property p ON i.propertyId = p.propertyId
            WHERE p.memberId = ?
            ORDER BY i.dateSent DESC";

  $stmt = $con->prepare($sql);
  $stmt->bind_param("i", $memberId);
  $stmt->execute();
  $result = $stmt->get_result();

  $inquiries = [];
  while ($row = $result->fetch_assoc()) {
    $inquiries[] = $row;
  }

  echo json_encode($inquiries);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
