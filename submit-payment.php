<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require 'connect.php';
require 'auth.php';

$user = requireAuth();
$memberId = $user->id;
$feeId = $_POST['memberFeeId'];
$methodId = $_POST['paymentMethodId'];
$date = date('Y-m-d H:i:s');

if (empty($_FILES['paymentRefImage'])) {
  echo json_encode(["status" => "fail", "message" => "Receipt image is required."]);
  exit;
}

$targetDir = "uploads/payments/";
if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

$ext = pathinfo($_FILES['paymentRefImage']['name'], PATHINFO_EXTENSION);
$fileName = "pay_" . $memberId . "_" . time() . "." . $ext;
$targetFilePath = $targetDir . $fileName;

if (move_uploaded_file($_FILES['paymentRefImage']['tmp_name'], $targetFilePath)) {
  $stmt = $con->prepare("INSERT INTO MemberPayment (memberId, paymentDate, paymentMethodId, paymentRefImage, memberFeeId) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("isisi", $memberId, $date, $methodId, $targetFilePath, $feeId);

  if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
  } else {
    echo json_encode(["status" => "fail", "message" => $con->error]);
  }
}
