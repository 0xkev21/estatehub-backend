<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require '../auth.php';
require '../connect.php';

// 1. Verify that the requester is an Admin
$admin = requireAdmin();
$adminId = $admin->id;

$data = json_decode(file_get_contents("php://input"), true);
$paymentId = $data['paymentId'] ?? null;
$memberId = $data['memberId'] ?? null;
$duration = $data['duration'] ?? null; // in days
$status = $data['status'] ?? ''; // 'approve' or 'reject'

if (!$paymentId || !$memberId || !$status) {
  http_response_code(400);
  echo json_encode(["status" => "fail", "message" => "Missing required data"]);
  exit;
}

// Start Transaction to ensure data integrity
$con->begin_transaction();

try {
  if ($status === 'approve') {
    // 2. Insert into PaymentApproval table
    $stmt = $con->prepare("INSERT INTO PaymentApproval (paymentId, adminId, approveDate) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $paymentId, $adminId);
    $stmt->execute();

    // 3. Get current expireDate of the Member
    $stmt = $con->prepare("SELECT expireDate FROM Member WHERE memberId = ?");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $currentExpiry = $result['expireDate'];
    $today = time();

    // logic: If current plan is still active, add duration to that date.
    // If expired or never had a plan, add duration to Today.
    if ($currentExpiry && strtotime($currentExpiry) > $today) {
      $baseDate = strtotime($currentExpiry);
    } else {
      $baseDate = $today;
    }

    $newExpiry = date('Y-m-d', strtotime("+$duration days", $baseDate));

    // 4. Update Member table with new expiry
    $stmt = $con->prepare("UPDATE Member SET expireDate = ? WHERE memberId = ?");
    $stmt->bind_param("si", $newExpiry, $memberId);
    $stmt->execute();
  } else {
    // Handle Rejection
    // You might want to add a 'cancelDate' or status to PaymentApproval
    $stmt = $con->prepare("INSERT INTO PaymentApproval (paymentId, adminId, cancelDate) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $paymentId, $adminId);
    $stmt->execute();
  }

  $con->commit();
  echo json_encode(["status" => "success", "message" => "Payment processed successfully"]);
} catch (Exception $e) {
  $con->rollback(); // Undo everything if any query fails
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
