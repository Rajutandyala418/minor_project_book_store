<?php
header('Content-Type: application/json');
session_start();
include(__DIR__.'/include/db_connect.php');

// PHPMailer
require __DIR__ . '/include/php_mailer/Exception.php';
require __DIR__ . '/include/php_mailer/PHPMailer.php';
require __DIR__ . '/include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ------------------- Get payment_id -------------------
$payment_id = $_POST['payment_id'] ?? 0;
if(!$payment_id){
    echo json_encode(['success'=>false,'message'=>'Payment ID missing']);
    exit;
}

// ------------------- Fetch payment & customer -------------------
$stmt = $conn->prepare("
    SELECT p.*, c.name AS customer_name, c.email, c.phone_number, c.gender
    FROM payments p
    JOIN customers c ON c.id = p.user_id
    WHERE p.payment_id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$payment){
    echo json_encode(['success'=>false,'message'=>'Payment not found']);
    exit;
}

// ------------------- Fetch orders -------------------
$ordersRes = $conn->query("SELECT * FROM orders WHERE payment_id={$payment_id}");
$orders = [];
while($o = $ordersRes->fetch_assoc()) $orders[] = $o;

// ------------------- Update orders & payment status -------------------
$conn->query("UPDATE orders SET status='Cancelled' WHERE payment_id={$payment_id}");
$conn->query("UPDATE payments SET payment_status='Cancelled' WHERE payment_id={$payment_id}");

// ------------------- Prepare email -------------------
$customerName = $payment['customer_name'];
$customerEmail = $payment['email'];
$totalAmount = $payment['total_amount'];

$message = "
<p>Dear <b>{$customerName}</b>,</p>
<p>We regret to inform you that your order with Payment ID <b>{$payment_id}</b> has been <b>cancelled</b>.</p>
<p>Refund of <b>₹{$totalAmount}</b> will be initiated to your original payment method within 5-7 working days.</p>
<p>Order Details:</p>
<ul>";
foreach($orders as $o){
    $message .= "<li>{$o['book_name']} by {$o['author']} - ₹{$o['price']} - Status: Cancelled</li>";
}
$message .= "</ul>
<p>We apologize for the inconvenience and thank you for shopping with us.</p>
<p>Best regards,<br>Book Store Team</p>
";

// ------------------- Send email -------------------
$mail = new PHPMailer(true);
$mailSent = false;
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'rvrbookstore.minor@gmail.com';
    $mail->Password = 'tmmt wlfb mvtx usvt'; // Use app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('rvrbookstore.minor@gmail.com', 'Book Store');
    $mail->addAddress($customerEmail, $customerName);
    $mail->isHTML(true);
    $mail->Subject = "Your Order Cancelled - Payment ID {$payment_id}";
    $mail->Body = $message;
    $mail->send();
    $mailSent = true;
} catch(Exception $e){
    error_log("Cancel Email Error: ".$e->getMessage());
}

// ------------------- Return JSON -------------------
if($mailSent){
    echo json_encode(['success'=>true,'message'=>'Order cancelled and email sent.']);
}else{
    echo json_encode(['success'=>false,'message'=>'Order cancelled but email sending failed.']);
}
?>
