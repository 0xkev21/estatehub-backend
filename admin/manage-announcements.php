<?php
header("Access-Control-Allow-Origin: *");
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

    $id = $_POST['announcementId'];
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $content = $_POST['announcement'];

    if (isset($_FILES['announcementImage']) && $_FILES['announcementImage']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../uploads/announcements/";
        $fileName = time() . '_' . basename($_FILES["announcementImage"]["name"]);
        $targetFile = $targetDir . $fileName;
        $dbPath = "uploads/announcements/" . $fileName;

        if (move_uploaded_file($_FILES["announcementImage"]["tmp_name"], $targetFile)) {

            // 2. Delete old physical image from server
            $oldRes = $con->query("SELECT announcementImage FROM announcement WHERE announcementId = $id");
            $oldRow = $oldRes->fetch_assoc();
            if ($oldRow['announcementImage'] && file_exists("../" . $oldRow['announcementImage'])) {
                unlink("../" . $oldRow['announcementImage']);
            }

            // 3. Update DB with new image path
            $sql = "UPDATE announcement SET title=?, description=?, announcement=?, announcementImage=? WHERE announcementId=?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ssssi", $title, $desc, $content, $dbPath, $id);
        } else {
            echo json_encode(["status" => "error", "message" => "File upload failed."]);
            exit;
        }
    } else {
        // No new image provided, keep the existing one in the DB
        $sql = "UPDATE announcement SET title=?, description=?, announcement=? WHERE announcementId=?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("sssi", $title, $desc, $content, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
}
