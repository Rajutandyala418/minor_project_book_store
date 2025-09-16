<?php
include(__DIR__ . '/include/db_connect.php');
require __DIR__ . '/fpdf/fpdf.php';

// ------------------ Get payment_id ------------------
$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
if (!$payment_id) die("❌ Payment ID missing.");

// ---------------- Fetch Payment Details ----------------
$pStmt = $conn->prepare("SELECT p.*, u.username 
                         FROM payments p
                         JOIN users u ON p.user_id = u.id
                         WHERE p.payment_id = ?");
$pStmt->bind_param("i", $payment_id);
$pStmt->execute();
$payment = $pStmt->get_result()->fetch_assoc();
$pStmt->close();
if (!$payment) die("❌ Payment not found.");

// ---------------- Fetch Orders ----------------
$oStmt = $conn->prepare("SELECT * FROM orders WHERE payment_id = ?");
$oStmt->bind_param("i", $payment_id);
$oStmt->execute();
$orderResult = $oStmt->get_result();
$orders = $orderResult->fetch_all(MYSQLI_ASSOC);
$oStmt->close();

// ---------------- Fetch Customer ----------------
$cStmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$cStmt->bind_param("i", $payment['user_id']);
$cStmt->execute();
$customer = $cStmt->get_result()->fetch_assoc();
$cStmt->close();

// ---------------- Fetch Address ----------------
$aStmt = $conn->prepare("SELECT * FROM address WHERE id = ?");
$aStmt->bind_param("i", $payment['user_id']);
$aStmt->execute();
$address = $aStmt->get_result()->fetch_assoc();
$aStmt->close();

// ---------------- PDF Setup ----------------
$pdf = new FPDF();
$pdf->AddPage();
$pdf->Rect(5, 5, 200, 287); // Outer border

// Title
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(190, 12, 'Booking Failed / Payment Failed', 0, 1, 'C');
$pdf->Ln(5);

// -------- Top Row with 3 Colors --------
$pdf->SetFont('Arial', 'B', 12);

// Payment ID (Red)
$pdf->SetFillColor(255,102,102);
$pdf->Cell(63, 10, "Payment ID: {$payment_id}", 1, 0, 'C', true);

// Payment Date (Orange)
$pdf->SetFillColor(255,204,153);
$pdf->Cell(63, 10, "Date: {$payment['payment_date']}", 1, 0, 'C', true);

// Status (Dark Red)
$pdf->SetFillColor(204,0,0);
$pdf->Cell(64, 10, "Status: {$payment['payment_status']}", 1, 1, 'C', true);
$pdf->Ln(5);

// -------- Function to Draw Tables --------
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

// -------- Orders Table --------
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetFillColor(200,200,200);
$pdf->Cell(190, 10, "Order Details", 1, 1, 'C', true);

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(220,220,255);
$pdf->Cell(10, 8, "#", 1, 0, 'C', true);
$pdf->Cell(50, 8, "Book Name", 1, 0, 'C', true);
$pdf->Cell(40, 8, "Author", 1, 0, 'C', true);
$pdf->Cell(20, 8, "Year", 1, 0, 'C', true);
$pdf->Cell(20, 8, "Pages", 1, 0, 'C', true);
$pdf->Cell(25, 8, "Price", 1, 0, 'C', true);
$pdf->Cell(25, 8, "Status", 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 12);
$i = 1;
foreach ($orders as $order) {
    $pdf->Cell(10, 8, $i++, 1, 0, 'C');
    $pdf->Cell(50, 8, $order['book_name'], 1, 0);
    $pdf->Cell(40, 8, $order['author'], 1, 0);
    $pdf->Cell(20, 8, $order['year'], 1, 0, 'C');
    $pdf->Cell(20, 8, $order['pages'], 1, 0, 'C');
    $pdf->Cell(25, 8, "Rs. " . $order['price'], 1, 0, 'C');
    $pdf->Cell(25, 8, $order['status'], 1, 1, 'C');
}
$pdf->Ln(5);

// -------- Payment Details --------
$paymentDetails = [
    "Method" => $payment['payment_method'],
    "Amount" => "Rs. " . $payment['total_amount'],
    "Date"   => $payment['payment_date'],
    "Status" => $payment['payment_status'],
    "User"   => $payment['username']
];
drawTable($pdf, "Payment Details", $paymentDetails);

// -------- Customer Details --------
$customerDetails = [
    "Name"   => $customer['name'],
    "Email"  => $customer['email'],
    "Phone"  => $customer['phone_number'],
    "Gender" => $customer['gender']
];
drawTable($pdf, "Customer Details", $customerDetails);

// -------- Address Details --------
$addressDetails = [
    "House"   => $address['house'],
    "Street"  => $address['street'],
    "Village" => $address['village'],
    "City"    => $address['city'],
    "State"   => $address['state'],
    "Pincode" => $address['pincode']
];
drawTable($pdf, "Address Details", $addressDetails);

// Output PDF
$pdf->Output("D", "BookingFailed_$payment_id.pdf");
?>
