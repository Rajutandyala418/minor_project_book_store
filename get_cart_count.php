<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/include/db_connect.php";

header("Content-Type: application/json");

// Get username from GET or session
if (isset($_GET['username'])) {
    $username = trim($_GET['username']);
} elseif (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
} else {
    echo json_encode(["status" => "error", "count" => 0, "message" => "No username provided"]);
    exit;
}

//1️⃣ Find user ID from users table
$sql = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["status" => "error", "count" => 0, "message" => "User not found"]);
    exit;
}

$user_id = $user['id'];

// 2️⃣ Count cart items for this user (cart.id = users.id)
$sql = "SELECT COUNT(cart_id) AS cnt FROM cart WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$count = $row['cnt'] ?? 0;

echo json_encode(["status" => "success", "count" => (int)$count]);
