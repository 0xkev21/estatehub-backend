<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once '../auth.php';
require_once '../connect.php';

$admin = requireAdmin();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=revenue_report.csv');

$output = fopen('php://output', 'w');
fputcsv($output, array('Payment ID', 'Member Name', 'Package', 'Amount (MMK)', 'Payment Date', 'Approval Date'));

$query = "SELECT mp.paymentId, CONCAT(m.firstName, ' ', m.lastName) as memberName, 
          mf.description, mf.amount, mp.paymentDate, pa.approveDate
          FROM memberpayment mp
          JOIN member m ON mp.memberId = m.memberId
          JOIN memberfee mf ON mf.memberfeeId = mp.memberfeeId
          JOIN paymentapproval pa ON pa.paymentId = mp.paymentId
          WHERE pa.approveDate IS NOT NULL
          ORDER BY pa.approveDate DESC";

$rows = $con->query($query);
while ($row = $rows->fetch_assoc()) {
  fputcsv($output, $row);
}

fclose($output);
exit;
