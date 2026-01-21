<?php
require '../auth.php';
require '../connect.php';

$admin = requireAdmin();

try {
  // Total Members
  $res1 = $con->query("SELECT COUNT(*) as total FROM Member");
  $members = $res1 ? $res1->fetch_assoc()['total'] : 0;

  // Pending Listings
  $res2 = $con->query("SELECT COUNT(*) as total FROM Property WHERE status = 'pending'");
  $pendingListings = $res2 ? $res2->fetch_assoc()['total'] : 0;

  // Count of Pending Payments (Waiting for Admin Approval)
  $res3 = $con->query("SELECT COUNT(*) as total FROM MemberPayment mp 
                        LEFT JOIN PaymentApproval pa ON mp.paymentId = pa.paymentId 
                        WHERE pa.approvalId IS NULL");
  $pendingPayments = $res3 ? $res3->fetch_assoc()['total'] : 0;

  // Total Revenue
  $res4 = $con->query("SELECT SUM(mf.amount) as total 
                        FROM MemberPayment mp 
                        JOIN MemberFee mf ON mp.memberfeeId = mf.memberfeeId
                        JOIN PaymentApproval pa ON mp.paymentId = pa.paymentId");

  if (!$res4) {
    throw new Exception("Revenue query failed: " . $con->error);
  }

  $revenue = $res4->fetch_assoc()['total'];

  echo json_encode([
    "status" => "success",
    "data" => [
      "members" => (int)$members,
      "pendingListings" => (int)$pendingListings,
      "pendingPayments" => (int)$pendingPayments,
      "totalRevenue" => (float)($revenue ?? 0)
    ]
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
