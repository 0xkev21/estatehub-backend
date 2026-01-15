<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
require_once '../auth.php';
require_once '../connect.php';

$admin = requireAdmin();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    // Fetch with Admin username join
    $res = $con->query("SELECT a.*, ad.name as author FROM announcement a 
                        JOIN admin ad ON a.adminId = ad.adminId 
                        ORDER BY date DESC");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;

    // 1. Get image path to delete physical file
    $res = $con->query("SELECT announcementImage FROM announcement WHERE announcementId = $id");
    $row = $res->fetch_assoc();
    if ($row['announcementImage']) unlink("../" . $row['announcementImage']);

    // 2. Delete from DB
    $con->query("DELETE FROM announcement WHERE announcementId = $id");
    echo json_encode(["status" => "success"]);
} elseif ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {

    // Handle Edit
    $id = $_POST['announcementId'];
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $content = $_POST['announcement'];

    // Update image if new one provided
    if (isset($_FILES['announcementImage'])) {
        $sql = "UPDATE announcement SET title=?, description=?, announcement=?, announcementImage=? WHERE announcementId=?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ssssi", $title, $desc, $content, $newImagePath, $id);
    } else {
        $sql = "UPDATE announcement SET title=?, description=?, announcement=? WHERE announcementId=?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("sssi", $title, $desc, $content, $id);
    }
    $stmt->execute();
    echo json_encode(["status" => "success"]);
}
