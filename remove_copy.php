<?php
require_once __DIR__ . "/include/db_connect.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$cart_id = intval($data["cart_id"] ?? 0);

if (!$cart_id) {
    echo json_encode(["success" => false, "message" => "Invalid cart_id"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ?");
$stmt->bind_param("i", $cart_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Copy removed"]);
} else {
    echo json_encode(["success" => false, "message" => "No matching copy found"]);
}

$stmt->close();
$conn->close();
?>
