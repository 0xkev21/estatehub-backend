<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  exit;
}

require 'connect.php';

$sql = "SELECT 
          r.regionId, r.region,
          d.districtId, d.district,
          t.townshipId, t.township
        FROM Region r
        JOIN District d ON r.regionId = d.regionId
        JOIN Township t ON d.districtId = t.districtId
        ORDER BY r.regionId, d.districtId, t.townshipId";

$result = $con->query($sql);

if ($result) {
  $rows = $result->fetch_all(MYSQLI_ASSOC);

  $regionsMap = [];

  foreach ($rows as $row) {
    $rId = $row['regionId'];
    $dId = $row['districtId'];

    // regions
    if (!isset($regionsMap[$rId])) {
      $regionsMap[$rId] = [
        'regionId' => (int)$rId,
        'region' => $row['region'],
        'districts' => []
      ];
    }

    // districts
    if (!isset($regionsMap[$rId]['districts'][$dId])) {
      $regionsMap[$rId]['districts'][$dId] = [
        'districtId' => (int)$dId,
        'district' => $row['district'],
        'townships' => []
      ];
    }

    // townships
    $regionsMap[$rId]['districts'][$dId]['townships'][] = [
      'townshipId' => (int)$row['townshipId'],
      'township' => $row['township']
    ];
  }

  foreach ($regionsMap as &$region) {
    $region['districts'] = array_values($region['districts']);
  }

  $finalData = array_values($regionsMap);

  http_response_code(200);
  echo json_encode(["status" => "success", "message" => "Locations fetched", "data" => $finalData]);
} else {
  http_response_code(500);
  echo json_encode(["status" => "fail", "message" => "Failed to fetch locations."]);
}

$con->close();
