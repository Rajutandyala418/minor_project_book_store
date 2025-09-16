<?php
include(__DIR__ . '/include/db_connect.php');
header("Content-Type: application/json");

// Read input
$data = json_decode(file_get_contents("php://input"), true);
$cart_id = intval($data['cart_id'] ?? 0);

if (!$cart_id) {
    echo json_encode(["success" => false, "message" => "Missing cart_id"]);
    exit;
}

// Fetch the existing cart row
$stmt = $conn->prepare("SELECT id, book_id, category, book_name, author, price, year, pages 
                        FROM cart WHERE cart_id = ? LIMIT 1");
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(["success" => false, "message" => "Cart row not found"]);
    exit;
}

// Insert a new row (auto-increment cart_id)
$stmt = $conn->prepare("INSERT INTO cart (id, book_id, category, book_name, author, price, year, pages) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
    "iisssdii",
    $row['id'],
    $row['book_id'],
    $row['category'],
    $row['book_name'],
    $row['author'],
    $row['price'],
    $row['year'],
    $row['pages']
);

if ($stmt->execute()) {
    $new_cart_id = $stmt->insert_id; // ✅ get the new auto-increment ID
    echo json_encode([
        "success" => true,
        "message" => "Book added to cart",
        "cart_id" => $new_cart_id // ✅ return it
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to add book",
        "error" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
