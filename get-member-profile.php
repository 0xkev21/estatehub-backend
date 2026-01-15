<?php
require_once 'auth.php';
$user = requireAuth();

$stmt = $con->prepare("SELECT firstName, lastName, email, expireDate FROM member WHERE memberId = ?");
$stmt->bind_param("i", $user->id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode($result);
