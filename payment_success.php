<?php
include(__DIR__ . '/include/db_connect.php');  // Adjust path

$username = $_POST['username'] ?? '';
$booksData = json_decode($_POST['booksData'] ?? '[]', true);

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

$finalTotal = $_POST['finalTotal'] ?? '0';
$paymentMethod = $_POST['paymentMethod'] ?? 'Unknown';

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

// ---- Insert payment ----
$paymentStatus = 'Completed';
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
// Use user_id instead of auto-increment
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

// ---- Insert each book into orders ----
// ---- Insert each book into orders ----
$orderDate = date('Y-m-d H:i:s'); // current date and time
$deliveryDate = date('Y-m-d H:i:s', strtotime('+7 days')); // 7 days later

$orderStmt = $conn->prepare("
    INSERT INTO orders 
    (order_id, user_id, payment_id, book_name, author, year, pages, price, order_date, delivery_date, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
");
if(!$orderStmt) die("Prepare failed (orders): ".$conn->error);

foreach ($booksData as $i => $book) {
    $bookName = $book['book_name'];
    $author   = $book['author'] ?? '';
    $year     = isset($book['year']) ? (int)$book['year'] : null;
    $pages    = isset($book['pages']) ? (int)$book['pages'] : null;
    $price    = (float)$book['price'];

    // Unique order_id per book
    $order_id = 'ORD-' . date('YmdHis') . '-' . ($i+1);

    // Bind parameters with correct types:
    // s = string, i = integer, d = double
    $orderStmt->bind_param(
        "siisssiiss",
        $order_id,      // s
        $user_id,       // i
        $payment_id,    // i
        $bookName,      // s
        $author,        // s
        $year,          // i
        $pages,         // i
        $price,         // d
        $orderDate,     // s
        $deliveryDate   // s
    );
    $orderStmt->execute();
}
$orderStmt->close();

// ---- Delete items from cart ----
$cartStmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND book_name = ?");
if($cartStmt){
    foreach ($booksData as $book) {
        $bookName = $book['book_name'];
        $cartStmt->bind_param("is", $user_id, $bookName);
        $cartStmt->execute();
    }
    $cartStmt->close();
}

$conn->close();

// ---- Redirect to success page ----
header("Location: book_success.php?payment_id=" . urlencode($payment_id));
exit();
?>
