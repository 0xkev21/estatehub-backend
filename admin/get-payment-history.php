<?php
require_once '../auth.php';

$admin = requireAdmin();

$search = isset($_GET['search']) ? $con->real_escape_string($_GET['search']) : '';
$startDate = isset($_GET['startDate']) && !empty($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) && !empty($_GET['endDate']) ? $_GET['endDate'] : null;

$sql = "SELECT 
            pa.approvalId, 
            pa.approveDate, 
            pa.cancelDate,
            COALESCE(pa.approveDate, pa.cancelDate) as processedDate,
            mf.amount,
            m.firstName, 
            m.lastName, 
            m.email,
            a.name 
        FROM PaymentApproval pa
        JOIN MemberPayment mp ON pa.paymentId = mp.paymentId
        JOIN Member m ON mp.memberId = m.memberId
        join memberfee mf on mf.memberfeeId = mp.memberFeeId
        JOIN Admin a ON pa.adminId = a.adminId";

$conditions = [];

if ($search) {
  $conditions[] = "(m.firstName LIKE '%$search%' OR m.lastName LIKE '%$search%' OR m.email LIKE '%$search%')";
}

if ($startDate && $endDate) {
  $conditions[] = "COALESCE(pa.approveDate, pa.cancelDate) BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
}

if (!empty($conditions)) {
  $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY processedDate DESC";

$res = $con->query($sql);
$data = [];

if ($res) {
  while ($row = $res->fetch_assoc()) {
    $data[] = $row;
  }
}

echo json_encode($data);
