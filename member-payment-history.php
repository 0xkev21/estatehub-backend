<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'connect.php';
require 'auth.php';

// 1. Authenticate the member
$userPayload = requireAuth();
$memberId = $userPayload->id ?? 0;

if ($memberId == 0) {
  http_response_code(401);
  echo json_encode(["status" => "fail", "message" => "Unauthorized access."]);
  exit;
}

try {
  // 2. Prepare SQL query joining Payment, Fee, and Approval tables
  // We use LEFT JOIN for PaymentApproval because a payment might not be approved yet.
  $sql = "SELECT 
                mp.paymentId, 
                mp.paymentDate, 
                mp.paymentRefImage, 
                mf.description AS feeDescription, 
                mf.amount, 
                pa.approveDate
            FROM MemberPayment mp
            JOIN MemberFee mf ON mp.memberFeeId = mf.memberFeeId
            LEFT JOIN PaymentApproval pa ON mp.paymentId = pa.paymentId
            WHERE mp.memberId = ?
            ORDER BY mp.paymentDate DESC";

  $stmt = $con->prepare($sql);
  $stmt->bind_param("i", $memberId);
  $stmt->execute();
  $result = $stmt->get_result();

  $history = [];
  while ($row = $result->fetch_assoc()) {
    $history[] = $row;
  }

  // 3. Return the data for the MembershipPage table
  echo json_encode($history);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
