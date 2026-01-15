<?php
require_once '../auth.php';
require_once '../connect.php';

$admin = requireAdmin();
$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    // Fetch existing fees
    $result = $con->query("SELECT * FROM MemberFee ORDER BY duration ASC");
    echo json_encode(["status" => "success", "data" => $result->fetch_all(MYSQLI_ASSOC)]);
  } elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['memberFeeId'] ?? null;
    $duration = $data['duration'];
    $amount = $data['amount'];
    $desc = $data['description'];

    if ($id) {
      // Updates duration, amount, and description for the specific plan
      $stmt = $con->prepare("UPDATE MemberFee SET duration=?, amount=?, description=? WHERE memberFeeId=?");
      $stmt->bind_param("iisi", $duration, $amount, $desc, $id);
      $stmt->execute();
      echo json_encode(["status" => "success", "message" => "Plan updated successfully"]);
    } else {
      echo json_encode(["status" => "error", "message" => "Updating new plans is disabled."]);
    }
  }
} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
