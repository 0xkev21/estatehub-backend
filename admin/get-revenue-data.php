<?php
require '../auth.php';
require '../connect.php';
$admin = requireAdmin();

// Get revenue for the last 6 months
$sql = "SELECT 
            DATE_FORMAT(pa.approveDate, '%b') as month, 
            SUM(mf.amount) as total 
        FROM MemberPayment mp
        JOIN MemberFee mf ON mp.memberfeeId = mf.memberfeeId
        JOIN PaymentApproval pa ON mp.paymentId = pa.paymentId
        WHERE pa.approveDate >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(pa.approveDate, '%Y-%m')
        ORDER BY pa.approveDate ASC";

$result = $con->query($sql);
$data = [];
while($row = $result->fetch_assoc()) {
    $data[] = ["name" => $row['month'], "revenue" => (float)$row['total']];
}

echo json_encode($data);