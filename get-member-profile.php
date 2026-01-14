<?php
require_once 'auth.php'; // Ensures valid token
$user = requireAuth();

// Fetch latest data from DB to ignore stale JWT claims
$stmt = $con->prepare("SELECT firstName, lastName, email, expireDate FROM member WHERE memberId = ?");
$stmt->bind_param("i", $user->id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode($result);
