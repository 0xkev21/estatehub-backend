<?php
header("Access-Control-Allow-Methods: DELETE");
require_once '../auth.php';
require_once '../connect.php';

$admin = requireAdmin();
$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    $result = $con->query("SELECT * FROM PaymentMethod");
    echo json_encode(["status" => "success", "data" => $result->fetch_all(MYSQLI_ASSOC)]);
  } elseif ($method === 'POST') {
    $name = $_POST['paymentMethodName'];
    $desc = $_POST['paymentDescription'];
    $number = $_POST['paymentNumber'];
    $id = $_POST['paymentMethodId'] ?? null;

    $imagePath = $_POST['existingImage'] ?? "";
    if (isset($_FILES['paymentMethodImage'])) {
      // Delete old image if updating and a new one is uploaded
      if ($id && !empty($_POST['existingImage'])) {
        $oldFile = '../' . $_POST['existingImage'];
        if (file_exists($oldFile)) unlink($oldFile);
      }

      $imagePath = 'uploads/methods/' . time() . '_' . $_FILES['paymentMethodImage']['name'];
      move_uploaded_file($_FILES['paymentMethodImage']['tmp_name'], '../' . $imagePath);
    }

    if ($id) {
      $stmt = $con->prepare("UPDATE PaymentMethod SET paymentMethodName=?, paymentDescription=?, paymentMethodImage=?, paymentNumber=? WHERE paymentMethodId=?");
      $stmt->bind_param("ssssi", $name, $desc, $imagePath, $number, $id);
      $stmt->execute();
      echo json_encode(["status" => "success", "message" => "Method updated"]);
    } else {
      $stmt = $con->prepare("INSERT INTO PaymentMethod (paymentMethodName, paymentDescription, paymentMethodImage, paymentNumber) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $name, $desc, $imagePath, $number);
      $stmt->execute();
      echo json_encode(["status" => "success", "message" => "Method added"]);
    }
  } elseif ($method === 'DELETE') {
    $id = $_GET['id'];

    // 1. Fetch the image path before deleting the record
    $stmtImg = $con->prepare("SELECT paymentMethodImage FROM PaymentMethod WHERE paymentMethodId = ?");
    $stmtImg->bind_param("i", $id);
    $stmtImg->execute();
    $res = $stmtImg->get_result()->fetch_assoc();

    if ($res && !empty($res['paymentMethodImage'])) {
      $filePath = '../' . $res['paymentMethodImage'];
      // 2. Delete the physical file from the server
      if (file_exists($filePath)) {
        unlink($filePath);
      }
    }

    // 3. Delete the record from the database
    $stmt = $con->prepare("DELETE FROM PaymentMethod WHERE paymentMethodId = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["status" => "success", "message" => "Method deleted and image removed"]);
  }
} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
