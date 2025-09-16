<?php
session_start();
include(__DIR__ . '/include/db_connect.php');

// PHPMailer
require __DIR__ . '/include/php_mailer/Exception.php';
require __DIR__ . '/include/php_mailer/PHPMailer.php';
require __DIR__ . '/include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ------------------ Get payment_id ------------------
$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
if (!$payment_id) die("❌ Payment ID missing.");

// ---------------- Fetch Payment + User ----------------
$stmt = $conn->prepare("
    SELECT p.*, u.username 
    FROM payments p
    JOIN users u ON p.user_id = u.id
    WHERE p.payment_id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$paymentResult = $stmt->get_result();
if ($paymentResult->num_rows === 0) die("❌ Payment not found.");
$payment = $paymentResult->fetch_assoc();
$stmt->close();

$user_id = $payment['user_id'];
$username = $payment['username'];

// ---------------- Fetch Orders ----------------
$orderStmt = $conn->prepare("SELECT * FROM orders WHERE payment_id = ?");
$orderStmt->bind_param("i", $payment_id);
$orderStmt->execute();
$ordersResult = $orderStmt->get_result();
$orders = $ordersResult->fetch_all(MYSQLI_ASSOC);
$orderStmt->close();

// ---------------- Fetch Customer ----------------
$custStmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$custStmt->bind_param("i", $user_id);
$custStmt->execute();
$customer = $custStmt->get_result()->fetch_assoc();
$custStmt->close();

if (!$customer) {
    $customer = [
        'name' => 'N/A',
        'email' => 'N/A',
        'phone_number' => 'N/A',
        'gender' => 'N/A'
    ];
}

// ---------------- Fetch Address ----------------
$addrStmt = $conn->prepare("SELECT * FROM address WHERE id = ?");
$addrStmt->bind_param("i", $user_id);
$addrStmt->execute();
$address = $addrStmt->get_result()->fetch_assoc();
$addrStmt->close();

if (!$address) {
    $address = [
        'house' => 'N/A',
        'street' => 'N/A',
        'village' => 'N/A',
        'city' => 'N/A',
        'state' => 'N/A',
        'pincode' => 'N/A'
    ];
}


function generateBookTicketPDF($payment, $orders, $customer, $address, $payment_id) {
    require_once __DIR__ . '/fpdf/fpdf.php';
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->Rect(5, 5, 200, 287); // Outer border

    // Title
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(190, 12, 'Book Order Invoice', 0, 1, 'C');
    $pdf->Ln(5);

    // Top row with 3 colors
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(173,216,230);
    $pdf->Cell(63, 10, "Payment ID: {$payment_id}", 1, 0, 'C', true);
    $pdf->SetFillColor(144,238,144);
    $pdf->Cell(63, 10, "Date: {$payment['payment_date']}", 1, 0, 'C', true);
    $pdf->SetFillColor(255,255,153);
    $pdf->Cell(64, 10, "Status: {$payment['payment_status']}", 1, 1, 'C', true);
    $pdf->Ln(5);

    function drawTable($pdf, $title, $dataArr) {
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(190, 10, $title, 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 12);
        foreach ($dataArr as $key => $value) {
            $pdf->SetFillColor(220,220,255);
            $pdf->Cell(60, 8, $key, 1, 0, 'L', true);
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(130, 8, $value, 1, 1, 'L', true);
        }
        $pdf->Ln(5);
    }

    // Orders Table
    $pdf->SetFont('Arial','B',14);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(190,10,"Order Details",1,1,'C',true);

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(220,220,255);
    $pdf->Cell(10,8,"#",1,0,'C',true);
    $pdf->Cell(50,8,"Book Name",1,0,'C',true);
    $pdf->Cell(40,8,"Author",1,0,'C',true);
    $pdf->Cell(20,8,"Year",1,0,'C',true);
    $pdf->Cell(20,8,"Pages",1,0,'C',true);
    $pdf->Cell(25,8,"Price",1,0,'C',true);
    $pdf->Cell(25,8,"Status",1,1,'C',true);

    $pdf->SetFont('Arial','',12);
    $i = 1;
    foreach($orders as $order){
        $pdf->Cell(10,8,$i++,1,0,'C');
        $pdf->Cell(50,8,$order['book_name'],1,0);
        $pdf->Cell(40,8,$order['author'],1,0);
        $pdf->Cell(20,8,$order['year'],1,0,'C');
        $pdf->Cell(20,8,$order['pages'],1,0,'C');
        $pdf->Cell(25,8,"Rs. ".$order['price'],1,0,'C');
        $pdf->Cell(25,8,$order['status'],1,1,'C');
    }
    $pdf->Ln(5);

    // Payment, Customer, Address tables
    drawTable($pdf, "Payment Details", [
        "Method"=>$payment['payment_method'],
        "Amount"=>"Rs. ".$payment['total_amount'],
        "Date"=>$payment['payment_date'],
        "Status"=>$payment['payment_status'],
        "User"=>$payment['username']
    ]);

    drawTable($pdf, "Customer Details", [
        "Name"=>$customer['name'],
        "Email"=>$customer['email'],
        "Phone"=>$customer['phone_number'],
        "Gender"=>$customer['gender']
    ]);

    drawTable($pdf, "Address Details", [
        "House"=>$address['house'],
        "Street"=>$address['street'],
        "Village"=>$address['village'],
        "City"=>$address['city'],
        "State"=>$address['state'],
        "Pincode"=>$address['pincode']
    ]);

    return $pdf->Output("S");
}

function sendBookEmail($toEmail, $toName, $subject, $payment_id){
    global $payment, $orders, $customer, $address;

    $pdfContent = generateBookTicketPDF($payment, $orders, $customer, $address, $payment_id);

    $ticketHTML = "<pre style='font-family:Arial,sans-serif; font-size:14px;'>
📚 Book Order Confirmation
────────────────────────
🆔 Payment ID: {$payment_id}
💰 Total Paid: ₹{$payment['total_amount']}
📅 Payment Date: {$payment['payment_date']}
✅ Status: {$payment['payment_status']}
────────────────────────
👤 Customer: {$customer['name']} | {$customer['email']} | {$customer['phone_number']}
────────────────────────
🏠 Address: {$address['house']}, {$address['street']}, {$address['village']}, {$address['city']}, {$address['state']} - {$address['pincode']}
────────────────────────
📚 Books Ordered:
";

    foreach ($orders as $i => $o) {
        $ticketHTML .= ($i+1) . ". {$o['book_name']} by {$o['author']} ({$o['year']}) | Pages: {$o['pages']} | Price: ₹{$o['price']} | Status: {$o['status']}\n";
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rvrbookstore.minor@gmail.com';
        $mail->Password   = 'tmmt wlfb mvtx usvt';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('rvrbookstore.minor@gmail.com','Book Store');
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->Body    = $ticketHTML;
        $mail->isHTML(true);

        $mail->addStringAttachment($pdfContent, "BookOrder_$payment_id.pdf");

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "<script>alert('❌ Mail Error: {$mail->ErrorInfo}');</script>";
        return false;
    }
}

// ---------------- Automatic email to customer ----------------
if (!isset($_SESSION['email_sent_' . $payment_id])) {
    if (sendBookEmail($customer['email'], $customer['name'], "Your Book Order - Payment #$payment_id", $payment_id)) {
        $_SESSION['email_sent_' . $payment_id] = true;
        echo "<script>alert('📧 Email sent automatically to {$customer['email']}');</script>";
    }
}

// ---------------- Handle manual email ----------------
if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['manual_email'])){
    $manual_email = $_POST['manual_email'];
    sendBookEmail($manual_email, $customer['name'], "Your Book Order - Payment #$payment_id", $payment_id);
    echo "<script>alert('Email sent successfully to $manual_email');</script>";
}

$ticketText = "📚 Book Order Confirmation
────────────────────────
🆔 Payment ID: {$payment_id}
💰 Total Paid: ₹{$payment['total_amount']}
📅 Payment Date: {$payment['payment_date']}
✅ Status: {$payment['payment_status']}
────────────────────────
👤 Customer: {$customer['name']} | {$customer['email']} | {$customer['phone_number']}
────────────────────────
🏠 Address: {$address['house']}, {$address['street']}, {$address['village']}, {$address['city']}, {$address['state']} - {$address['pincode']}
────────────────────────
📚 Books Ordered:
";

foreach ($orders as $i => $o) {
    $ticketText .= ($i+1) . ". {$o['book_name']} by {$o['author']} ({$o['year']}) | Pages: {$o['pages']} | Price: ₹{$o['price']} | Status: {$o['status']}\n";
}

$phoneNumber = preg_replace('/[^0-9]/','',$customer['phone_number']);
$whatsappLink = "https://wa.me/{$phoneNumber}?text={$ticketText}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Book Order Success</title>
<style>
body { font-family:'Poppins',sans-serif; background:#111; color:#fff; margin:0; padding:20px; }
.top-bar { display:flex; justify-content:space-between; align-items:center; }
.dashboard-btn { background:#ffde59; padding:10px 20px; border-radius:5px; color:#111; font-weight:bold; text-decoration:none; }
.container { margin-top:30px; }
table { width:100%; border-collapse:collapse; margin-bottom:20px; background:rgba(0,0,0,0.4); }
table, th, td { border:1px solid #555; }
th, td { padding:10px; text-align:left; }
th { background:#222; color:#ffde59; }
h2,h3 { color:#ffde59; margin-bottom:10px; }
.btn { background:linear-gradient(90deg,#ff512f,#dd2476); padding:10px 20px; border-radius:5px; text-decoration:none; color:white; font-weight:bold; margin-right:10px; cursor:pointer; border:none; }
.bottom-btns { margin-top:20px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
input[type=email] { padding:8px; border-radius:5px; border:none; width:220px; }
</style>
</head>
<body>

<div class="top-bar">
  <a class="dashboard-btn" href="dashboard.html?username=<?= urlencode($username) ?>">Back to Dashboard</a>
</div>

<div class="container">
<h2>🎉 Payment Successful - ID: <?= $payment_id ?></h2>

<h3>💰 Payment Details</h3>
<table>
<tr><th>Total Paid</th><td>₹<?= $payment['total_amount'] ?></td></tr>
<tr><th>Payment Date</th><td><?= $payment['payment_date'] ?></td></tr>
<tr><th>Payment Method</th><td><?= $payment['payment_method'] ?></td></tr>
<tr><th>Payment Status</th><td><?= $payment['payment_status'] ?></td></tr>
</table>

<h3>📚 Orders</h3>
<table>
<tr><th>Book</th><th>Author</th><th>Year</th><th>Pages</th><th>Price</th><th>Status</th></tr>
<?php foreach($orders as $o): ?>
<tr>
  <td><?= htmlspecialchars($o['book_name']) ?></td>
  <td><?= htmlspecialchars($o['author']) ?></td>
  <td><?= htmlspecialchars($o['year']) ?></td>
  <td><?= htmlspecialchars($o['pages']) ?></td>
  <td>₹<?= htmlspecialchars($o['price']) ?></td>
  <td><?= htmlspecialchars($o['status']) ?></td>
</tr>
<?php endforeach; ?>
</table>

<h3>👤 Customer Details</h3>
<table>
<tr><th>Name</th><td><?= $customer['name'] ?></td></tr>
<tr><th>Email</th><td><?= $customer['email'] ?></td></tr>
<tr><th>Phone</th><td><?= $customer['phone_number'] ?></td></tr>
<tr><th>Gender</th><td><?= $customer['gender'] ?></td></tr>
</table>

<h3>🏠 Address</h3>
<table>
<tr><th>House</th><td><?= $address['house'] ?></td></tr>
<tr><th>Street</th><td><?= $address['street'] ?></td></tr>
<tr><th>Village</th><td><?= $address['village'] ?></td></tr>
<tr><th>City</th><td><?= $address['city'] ?></td></tr>
<tr><th>State</th><td><?= $address['state'] ?></td></tr>
<tr><th>Pincode</th><td><?= $address['pincode'] ?></td></tr>
</table>

<div class="bottom-btns">
  <a class="btn" href="download_ticket.php?payment_id=<?= $payment_id ?>" target="_blank">Download Ticket (PDF)</a>
  <form method="post" onsubmit="return true;">
    <input type="email" name="manual_email" placeholder="Enter email to send" required>
    <button type="submit" class="btn">Send Email</button>
  </form>
  <a class="btn" href="<?= $whatsappLink ?>" target="_blank">Send WhatsApp</a>
</div>
</div>
</body>
</html>
