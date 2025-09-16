<?php
include(__DIR__ . '/include/db_connect.php');
header("Content-Type: application/json");

$username = $_GET['username'] ?? '';

if (!$username) {
    echo json_encode(["status" => "error", "message" => "No username provided"]);
    exit;
}

// 1️⃣ Get user id from username
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}
$stmt->close();

// 2️⃣ Get cart items for that user (⚠️ use `id` not `user_id`)
$sql = "SELECT cart_id, id AS user_id, book_id, category, book_name, author, price, year, pages 
        FROM cart WHERE id = ? ORDER BY cart_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

if (empty($items)) {
    echo json_encode(["status" => "error", "message" => "No items found in cart"]);
} else {
    echo json_encode(["status" => "success", "items" => $items]);
}

$stmt->close();
$conn->close();
?>
