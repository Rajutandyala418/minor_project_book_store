<?php
header('Content-Type: application/json');
include(__DIR__.'/include/db_connect.php');

// PHPMailer & PDF functions
require __DIR__ . '/include/php_mailer/Exception.php';
require __DIR__ . '/include/php_mailer/PHPMailer.php';
require __DIR__ . '/include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include your existing PDF & sendBookEmail functions
require __DIR__.'/functions_email_pdf.php'; // Make sure it contains generateBookTicketPDF() and sendBookEmail()

$payment_id = $_POST['payment_id'] ?? 0;
if(!$payment_id){
    echo json_encode(['success'=>false,'message'=>'Payment ID missing']);
    exit;
}

// Fetch payment & customer details
$stmt = $conn->prepare("SELECT p.*, c.name AS customer_name, c.email, c.phone_number, c.gender, 
                                a.house,a.street,a.village,a.city,a.state,a.pincode
                        FROM payments p
                        JOIN customers c ON c.user_id=p.user_id
                        JOIN address a ON a.user_id=p.user_id
                        WHERE p.payment_id=?");
$stmt->bind_param("i",$payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$payment){
    echo json_encode(['success'=>false,'message'=>'Payment not found']);
    exit;
}

// Fetch orders
$ordersRes = $conn->query("SELECT * FROM orders WHERE payment_id={$payment_id}");
$orders = [];
while($o = $ordersRes->fetch_assoc()) $orders[] = $o;

// Fetch customer & address arrays for email function
$customer = [
    'name'=>$payment['customer_name'],
    'email'=>$payment['email'],
    'phone_number'=>$payment['phone_number'],
    'gender'=>$payment['gender']
];
$address = [
    'house'=>$payment['house'],
    'street'=>$payment['street'],
    'village'=>$payment['village'],
    'city'=>$payment['city'],
    'state'=>$payment['state'],
    'pincode'=>$payment['pincode']
];

// Update database
$conn->query("UPDATE orders SET status='Cancelled' WHERE payment_id={$payment_id}");
$conn->query("UPDATE payments SET payment_status='Cancelled' WHERE payment_id={$payment_id}");

// Update $orders and $payment status for email/PDF
foreach($orders as &$o) $o['status']='Cancelled';
$payment['payment_status']='Cancelled';

// Send email
$mailSent = sendBookEmail($payment['email'], $payment['customer_name'], "Order Cancelled", $payment_id);

if($mailSent){
    echo json_encode(['success'=>true,'message'=>'Order cancelled and email sent.']);
}else{
    echo json_encode(['success'=>false,'message'=>'Order cancelled but email sending failed.']);
}
?>
