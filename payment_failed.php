<?php
session_start();
include(__DIR__ . '/include/db_connect.php'); // Database connection

// ---- Get all POST or session data ----
$username = $_POST['username'] ?? ($_SESSION['username'] ?? 'Guest');
$booksData = $_POST['booksData'] ?? '[]';
$books = json_decode($booksData, true);

$travellerName = $_POST['travellerName'] ?? '';
$travellerEmail = $_POST['travellerEmail'] ?? '';
$travellerPhone = $_POST['travellerPhone'] ?? '';
$travellerGender = $_POST['travellerGender'] ?? '';

$houseNo = $_POST['houseNo'] ?? '';
$street = $_POST['street'] ?? '';
$village = $_POST['village'] ?? '';
$city = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$pincode = $_POST['pincode'] ?? '';

$baseAmount = $_POST['baseAmount'] ?? '0';
$gstAmount = $_POST['gstAmount'] ?? '0';
$discountAmount = $_POST['discountAmount'] ?? '0';
$finalTotal = $_POST['finalTotal'] ?? '0';
$paymentMethod = $_POST['paymentMethod'] ?? 'N/A';

// ---- Get user_id ----
$user_id = null;
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
if(!$stmt) die("Prepare failed: ".$conn->error);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->bind_result($user_id);
    $stmt->fetch();
} else {
    die("❌ User not found for username: $username");
}
$stmt->close();

// ---- Insert failed payment ----
$paymentStatus = 'Failed';
$paymentDate = date('Y-m-d H:i:s');
$stmt = $conn->prepare("
    INSERT INTO payments (user_id, payment_method, payment_status, payment_date, total_amount)
    VALUES (?, ?, ?, ?, ?)
");
if(!$stmt) die("Prepare failed (payments): ".$conn->error);
$stmt->bind_param("isssd", $user_id, $paymentMethod, $paymentStatus, $paymentDate, $finalTotal);
$stmt->execute();
$payment_id = $stmt->insert_id;
$stmt->close();

// ---- Insert or update customer ----
$stmt = $conn->prepare("
    INSERT INTO customers (id, name, email, phone_number, gender)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    name=VALUES(name),
    email=VALUES(email),
    phone_number=VALUES(phone_number),
    gender=VALUES(gender)
");
if(!$stmt) die("Prepare failed (customers): ".$conn->error);
$stmt->bind_param("issss", $user_id, $travellerName, $travellerEmail, $travellerPhone, $travellerGender);
$stmt->execute();
$stmt->close();

// ---- Insert or update address ----
$stmt = $conn->prepare("
    INSERT INTO address (id, house, street, village, city, state, pincode)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    house=VALUES(house),
    street=VALUES(street),
    village=VALUES(village),
    city=VALUES(city),
    state=VALUES(state),
    pincode=VALUES(pincode)
");
if(!$stmt) die("Prepare failed (address): ".$conn->error);
$stmt->bind_param("issssss", $user_id, $houseNo, $street, $village, $city, $state, $pincode);
$stmt->execute();
$stmt->close();

// ---- Insert orders with status 'Cancelled' ----
$orderDate = date('Y-m-d H:i:s');
$deliveryDate = date('Y-m-d H:i:s', strtotime('+7 days'));
$orderStmt = $conn->prepare("
    INSERT INTO orders 
    (order_id, user_id, payment_id, book_name, author, year, pages, price, order_date, delivery_date, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Cancelled')
");
if(!$orderStmt) die("Prepare failed (orders): ".$conn->error);

foreach ($books as $i => $book) {
    $bookName = $book['book_name'];
    $author   = $book['author'] ?? '';
    $year     = isset($book['year']) ? (int)$book['year'] : null;
    $pages    = isset($book['pages']) ? (int)$book['pages'] : null;
    $price    = (float)$book['price'];
    $order_id = 'ORD-' . date('YmdHis') . '-' . ($i+1);

    $orderStmt->bind_param(
        "siisssiiss",
        $order_id,
        $user_id,
        $payment_id,
        $bookName,
        $author,
        $year,
        $pages,
        $price,
        $orderDate,
        $deliveryDate
    );
    $orderStmt->execute();
}
$orderStmt->close();

$conn->close();

// ---- Redirect to booking_failed.php with payment_id ----
header("Location: booking_failed.php?payment_id=" . urlencode($payment_id));
exit();
?>
