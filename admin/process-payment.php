<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require '../auth.php';
require '../connect.php';

$admin = requireAdmin();
$adminId = $admin->id; // Object notation as previously established

$data = json_decode(file_get_contents("php://input"), true);
$paymentId = $data['paymentId'] ?? null;
$memberId = $data['memberId'] ?? null;
$duration = $data['duration'] ?? 0; 
$status = $data['status'] ?? ''; // 'approve' or 'reject'
$remarks = $data['remarks'] ?? ''; // Added for rejection reason

if (!$paymentId || !$memberId || !$status) {
    http_response_code(400);
    echo json_encode(["status" => "fail", "message" => "Missing required data"]);
    exit;
}

$con->begin_transaction();

try {
    if ($status === 'approve') {
        // 1. Log Approval
        $stmt = $con->prepare("INSERT INTO PaymentApproval (paymentId, adminId, approveDate) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $paymentId, $adminId);
        $stmt->execute();

        // 2. Calculate New Expiry
        $stmt = $con->prepare("SELECT expireDate FROM Member WHERE memberId = ?");
        $stmt->bind_param("i", $memberId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $currentExpiry = $result['expireDate'];
        $today = time();

        // If plan is active, extend from current expiry; otherwise, start from today.
        if ($currentExpiry && strtotime($currentExpiry) > $today) {
            $baseDate = strtotime($currentExpiry);
        } else {
            $baseDate = $today;
        }

        $newExpiry = date('Y-m-d', strtotime("+$duration days", $baseDate));

        // 3. Update Member
        $stmt = $con->prepare("UPDATE Member SET expireDate = ? WHERE memberId = ?");
        $stmt->bind_param("si", $newExpiry, $memberId);
        $stmt->execute();

        $msg = "Payment approved and membership extended until $newExpiry";
    } 
    else if ($status === 'reject') {
        // Log Rejection
        $stmt = $con->prepare("INSERT INTO PaymentApproval (paymentId, adminId, cancelDate) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $paymentId, $adminId);
        $stmt->execute();
        
        $msg = "Payment has been rejected. No changes were made to membership duration.";
    }

    $con->commit();
    echo json_encode(["status" => "success", "message" => $msg]);

} catch (Exception $e) {
    $con->rollback();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}