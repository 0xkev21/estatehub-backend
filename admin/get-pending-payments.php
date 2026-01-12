<?php
require '../auth.php';
require '../connect.php';

$admin = requireAdmin();

$sql = "SELECT 
            mp.paymentId, 
            mp.paymentRefImage, 
            mp.paymentDate,
            m.memberId,
            m.firstName, 
            m.lastName, 
            m.email,
            mf.description, 
            mf.amount, 
            mf.duration
        FROM MemberPayment mp
        JOIN Member m ON mp.memberId = m.memberId
        JOIN MemberFee mf ON mp.memberfeeId = mf.memberfeeId
        LEFT JOIN PaymentApproval pa ON mp.paymentId = pa.paymentId
        WHERE pa.approvalId IS NULL
        ORDER BY mp.paymentDate DESC";

$result = $con->query($sql);
$payments = [];

while ($row = $result->fetch_assoc()) {
  $payments[] = $row;
}

echo json_encode($payments);
