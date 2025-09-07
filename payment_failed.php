<?php
session_start();
require __DIR__ . '/include/php_mailer/Exception.php';
require __DIR__ . '/include/php_mailer/PHPMailer.php';
require __DIR__ . '/include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

// ---- PDF Generation ----
require_once __DIR__ . '/fpdf/fpdf.php';
function generateFailedPDF($username, $books, $travellerName, $travellerEmail, $travellerPhone, $travellerGender, $houseNo, $street, $village, $city, $state, $pincode, $baseAmount, $gstAmount, $discountAmount, $finalTotal, $paymentMethod) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,'Payment Failed / Order Cancelled',0,1,'C');
    $pdf->Ln(5);

    // Order details
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Order Details:',0,1);
    $pdf->SetFont('Arial','',12);
    if(!empty($books)){
        foreach($books as $i=>$b){
            $pdf->Cell(0,8,($i+1).". {$b['book_name']} by {$b['author']} | Qty: {$b['quantity']} | Price: ₹{$b['price']}",0,1);
        }
    } else {
        $pdf->Cell(0,8,'No book data',0,1);
    }
    $pdf->Ln(3);

    // Traveller details
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Traveller Details:',0,1);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8,"Name: $travellerName",0,1);
    $pdf->Cell(0,8,"Email: $travellerEmail",0,1);
    $pdf->Cell(0,8,"Phone: $travellerPhone",0,1);
    $pdf->Cell(0,8,"Gender: $travellerGender",0,1);
    $pdf->Ln(3);

    // Payment details
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Payment Details:',0,1);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8,"Base: ₹$baseAmount",0,1);
    $pdf->Cell(0,8,"GST: ₹$gstAmount",0,1);
    $pdf->Cell(0,8,"Discount: ₹$discountAmount",0,1);
    $pdf->Cell(0,8,"Total: ₹$finalTotal",0,1);
    $pdf->Cell(0,8,"Payment Method: $paymentMethod",0,1);
    $pdf->Cell(0,8,"Payment Status: FAILED / CANCELLED",0,1);
    $pdf->Ln(3);

    // Address
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Address:',0,1);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8,"$houseNo, $street, $village, $city, $state - $pincode",0,1);

    return $pdf->Output("S");
}

// ---- Send Email Automatically ----
function sendFailedEmail($toEmail, $toName, $books, $travellerName, $travellerEmail, $travellerPhone, $travellerGender, $houseNo, $street, $village, $city, $state, $pincode, $baseAmount, $gstAmount, $discountAmount, $finalTotal, $paymentMethod){
    $pdfContent = generateFailedPDF($toName, $books, $travellerName, $travellerEmail, $travellerPhone, $travellerGender, $houseNo, $street, $village, $city, $state, $pincode, $baseAmount, $gstAmount, $discountAmount, $finalTotal, $paymentMethod);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com';
        $mail->Password   = 'pjhg nwnt haac nsiu';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com','Book Store');
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = "Order Cancelled / Payment Failed";
        $mail->Body = "Dear $toName,\n\nYour order has been cancelled and payment failed. Please find attached PDF for details.\n\nRegards,\nBook Store";
        $mail->isHTML(false);
        $mail->addStringAttachment($pdfContent,"Order_Cancelled.pdf");
        $mail->send();
    } catch (Exception $e) {
        // silently ignore mail errors
    }
}

// Send email automatically once
if(!isset($_SESSION['failed_email_sent'])){
    sendFailedEmail($travellerEmail, $travellerName, $books, $travellerName, $travellerEmail, $travellerPhone, $travellerGender, $houseNo, $street, $village, $city, $state, $pincode, $baseAmount, $gstAmount, $discountAmount, $finalTotal, $paymentMethod);
    $_SESSION['failed_email_sent'] = true;
}

// Generate PDF for download
$pdfFileName = "Order_Cancelled.pdf";
$pdfContent = generateFailedPDF($username, $books, $travellerName, $travellerEmail, $travellerPhone, $travellerGender, $houseNo, $street, $village, $city, $state, $pincode, $baseAmount, $gstAmount, $discountAmount, $finalTotal, $paymentMethod);
file_put_contents(__DIR__.'/'.$pdfFileName, $pdfContent);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Failed / Order Cancelled</title>
<style>
body {font-family:Arial,sans-serif;background:#111;color:#fff;margin:0;padding:20px;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.btn {background:#ff4d4d;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;}
.table {width:100%;border-collapse:collapse;margin-bottom:20px;}
.table td, .table th {border:1px solid #555;padding:8px;text-align:left;}
.table th {background:#222;color:#ff4d4d;}
h2,h3{color:#ff4d4d;}
</style>
</head>
<body>
<div class="header">
  <h2>Payment Failed / Order Cancelled</h2>
  <a href="dashboard.html?username=<?= urlencode($username) ?>" class="btn">Back to Dashboard</a>
</div>

<h3>Order Details</h3>
<table class="table">
<tr><th>#</th><th>Book</th><th>Author</th><th>Qty</th><th>Price</th><th>Total</th></tr>
<?php if(!empty($books)): foreach($books as $i=>$b): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($b['book_name']) ?></td>
<td><?= htmlspecialchars($b['author']) ?></td>
<td><?= htmlspecialchars($b['quantity']) ?></td>
<td>₹<?= number_format($b['price'],2) ?></td>
<td>₹<?= number_format($b['price']*$b['quantity'],2) ?></td>
</tr>
<?php endforeach; else: ?><tr><td colspan="6">No book data</td></tr><?php endif; ?>
</table>

<h3>Traveller Details</h3>
<table class="table">
<tr><td>Name</td><td><?= htmlspecialchars($travellerName) ?></td></tr>
<tr><td>Email</td><td><?= htmlspecialchars($travellerEmail) ?></td></tr>
<tr><td>Phone</td><td><?= htmlspecialchars($travellerPhone) ?></td></tr>
<tr><td>Gender</td><td><?= htmlspecialchars($travellerGender) ?></td></tr>
</table>

<h3>Payment Details</h3>
<table class="table">
<tr><td>Base</td><td>₹<?= htmlspecialchars($baseAmount) ?></td></tr>
<tr><td>GST</td><td>₹<?= htmlspecialchars($gstAmount) ?></td></tr>
<tr><td>Discount</td><td>₹<?= htmlspecialchars($discountAmount) ?></td></tr>
<tr><td>Total</td><td>₹<?= htmlspecialchars($finalTotal) ?></td></tr>
<tr><td>Method</td><td><?= htmlspecialchars($paymentMethod) ?></td></tr>
<tr><td>Status</td><td>FAILED / CANCELLED</td></tr>
</table>

<h3>Address</h3>
<table class="table">
<tr><td>House</td><td><?= htmlspecialchars($houseNo) ?></td></tr>
<tr><td>Street</td><td><?= htmlspecialchars($street) ?></td></tr>
<tr><td>Village</td><td><?= htmlspecialchars($village) ?></td></tr>
<tr><td>City</td><td><?= htmlspecialchars($city) ?></td></tr>
<tr><td>State</td><td><?= htmlspecialchars($state) ?></td></tr>
<tr><td>Pincode</td><td><?= htmlspecialchars($pincode) ?></td></tr>
</table>

<a href="<?= $pdfFileName ?>" class="btn" target="_blank">Download Ticket (PDF)</a>
</body>
</html>
