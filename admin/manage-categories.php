<?php
require_once '../auth.php';
require_once '../connect.php';

$admin = requireAdmin();
$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? ''; // 'property' or 'listing'

try {
  if ($method === 'GET') {
    $table = ($type === 'property') ? 'PropertyType' : 'ListingType';
    $result = $con->query("SELECT * FROM $table");
    echo json_encode(["status" => "success", "data" => $result->fetch_all(MYSQLI_ASSOC)]);
  } elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $value = $data['name'];

    if ($type === 'property') {
      // Matches +addPropertyType()
      $stmt = $con->prepare("INSERT INTO PropertyType (propertyType) VALUES (?)");
    } else {
      // Matches +addListingType()
      $stmt = $con->prepare("INSERT INTO ListingType (listingType) VALUES (?)");
    }

    $stmt->bind_param("s", $value);
    $stmt->execute();
    echo json_encode(["status" => "success", "message" => "Added successfully"]);
  } elseif ($method === 'DELETE') {
    $id = $_GET['id'];
    if ($type === 'property') {
      // Matches +removePropertytype()
      $stmt = $con->prepare("DELETE FROM PropertyType WHERE propertyTypeId = ?");
    } else {
      // Matches +removeListingtype()
      $stmt = $con->prepare("DELETE FROM ListingType WHERE listingTypeId = ?");
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["status" => "success", "message" => "Removed successfully"]);
  }
} catch (Exception $e) {
  echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
