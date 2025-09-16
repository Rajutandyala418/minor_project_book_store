<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/include/db_connect.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['username'], $data['category'], $data['book_name'], $data['author'], $data['price'], $data['year'], $data['pages'])) {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

$username = $data['username'];
$category = $data['category'];
$book_name = $data['book_name'];
$author   = $data['author'];
$price    = $data['price'];
$year     = $data['year'];
$pages    = $data['pages'];

//1️⃣ Get user id
$sql = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$user_id = $user['id'];

// 2️⃣ Generate book_id = category+year+uniqid
$book_id = $year.$pages;

// 3️⃣ Insert into cart
$sql = "INSERT INTO cart (id, book_id, category, book_name, author, price, year, pages) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issssdii", $user_id, $book_id, $category, $book_name, $author, $price, $year, $pages);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Book added to cart"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
}
