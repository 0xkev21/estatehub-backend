<?php
require 'connect.php';
require __DIR__ . '/vendor/autoload.php';

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

use Firebase\JWT\JWT;
use Firebase\JWT\KEY;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

function getAuthorizationHeader()
{
  if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    return trim($_SERVER['HTTP_AUTHORIZATION']);
  }
  if (function_exists('getallheaders')) {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
      return trim($headers['Authorization']);
    }
    if (isset($headers['authorization'])) {
      return trim($headers['authorization']);
    }
  }
  return null;
}

function getBearerToken()
{
  $headers = getAuthorizationHeader();
  if (!empty($headers)) {
    if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
      return $matches[1];
    }
  }
  return null;
}

// Genenral Authentication
function requireAuth()
{
  global $JWT_SECRET, $JWT_ALGO;
  $token = getBearerToken();
  if (!$token) {
    http_response_code(401);
    echo json_encode(["status" => "fail", "message" => "Unauthorized: Missing token"]);
    exit;
  }
  try {
    $decoded = JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));
    $GLOBALS['currentUser'] = $decoded;
    return $GLOBALS['currentUser'];
  } catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "fail", "message" => "Unauthorized: " . $e->getMessage()]);
    exit;
  }
}

// Paid Member
function requirePaidMember()
{
  global $con;

  $user = requireAuth();
  $memberId = $user->id;

  $stmt = $con->prepare("SELECT expireDate FROM member WHERE memberId = ?");
  $stmt->bind_param("i", $memberId);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();

  // 1. Handle NULL (Never paid / No plan)
  if (!$result || is_null($result['expireDate'])) {
    http_response_code(403);
    echo json_encode(["status" => "fail", "message" => "Membership required to post listings."]);
    exit;
  }

  // 2. Handle Expired
  $expiry = strtotime($result['expireDate']);
  $today = strtotime(date('Y-m-d'));

  if ($expiry < $today) {
    http_response_code(403);
    echo json_encode([
      "status" => "fail",
      "message" => "Your membership expired on " . $result['expireDate'] . ". Please renew."
    ]);
    exit;
  }

  return $user;
}

// Admin
function requireAdmin()
{
  // validate the token is valid and not expired
  $user = requireAuth();

  // Check the 'role'
  if (!isset($user->role) || $user->role !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode([
      "status" => "fail",
      "message" => "Access denied: Admin privileges required."
    ]);
    exit;
  }

  return $user;
}
