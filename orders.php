<?php
session_start();
include(__DIR__ . '/include/db_connect.php'); 
require __DIR__ . '/include/php_mailer/PHPMailer.php';
require __DIR__ . '/include/php_mailer/SMTP.php';
require __DIR__ . '/include/php_mailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ------------------ Functions ------------------
function sendOrderEmail($toEmail, $toName, $subject, $payment, $orders, $statusMessage){
    require_once __DIR__ . '/fpdf/fpdf.php';
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,"Order Invoice",0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Ln(5);

    foreach($orders as $i=>$o){
        $pdf->Cell(0,8,($i+1).". {$o['book_name']} by {$o['author']} ({$o['year']}) - ₹{$o['price']} - Status: {$o['status']}",0,1);
    }

    $pdfContent = $pdf->Output("S");

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'varahibusbooking@gmail.com';
        $mail->Password = 'pjhg nwnt haac nsiu';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('varahibusbooking@gmail.com','Book Store');
        $mail->addAddress($toEmail,$toName);
        $mail->Subject = $subject;
        $mail->Body = $statusMessage;
        $mail->isHTML(true);
        $mail->addStringAttachment($pdfContent,"Order_{$payment['payment_id']}.pdf");
        $mail->send();
    } catch(Exception $e){
        error_log("Email Error: ".$e->getMessage());
    }
}

// ------------------ Fetch Orders ------------------
$username = $_GET['username'] ?? '';
if (!$username) die("❌ Username missing.");

$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param("s",$username);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) die("❌ User not found.");
$stmt->close();

$paymentsRes = $conn->query("
    SELECT p.*, 
           c.name AS customer_name, c.email, c.phone_number, c.gender, 
           a.house,a.street,a.village,a.city,a.state,a.pincode
    FROM payments p
    LEFT JOIN customers c ON c.id = p.user_id
    LEFT JOIN address a   ON a.id = p.user_id
    WHERE p.user_id={$user_id}
    ORDER BY p.payment_date DESC
");
if(!$paymentsRes) die("SQL Error: ".$conn->error);

$ordersData = [];
while($payment = $paymentsRes->fetch_assoc()){
    $payment_id = $payment['payment_id'];
    $ordersRes = $conn->query("SELECT * FROM orders WHERE payment_id={$payment_id}");
    if(!$ordersRes) die("SQL Error (orders): ".$conn->error);

    $orders = [];
    while($o = $ordersRes->fetch_assoc()) $orders[] = $o;

    // ---------------- Auto Update Delivered ----------------
    $today = new DateTime();
    $deliveryDate = new DateTime($orders[0]['delivery_date']);
    $status = $orders[0]['status'];

    if($status != 'Cancelled' && $status != 'Delivered' && $today >= $deliveryDate){
        // Update order status to Delivered in DB
        foreach($orders as $o){
            $conn->query("UPDATE orders SET status='Delivered' WHERE order_id='{$o['order_id']}'");
        }
        $status = 'Delivered';
        // Send email
        sendOrderEmail($payment['email'],$payment['customer_name'],
            "Your Order Delivered - Payment ID {$payment_id}",
            $payment,$orders,"🎉 Your order has been delivered successfully.");
        // Update orders array to reflect delivered
        foreach($orders as &$o) $o['status']='Delivered';
    }

    $ordersData[] = [
        'payment'=>$payment,
        'orders'=>$orders
    ];
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders</title>
<style>
body{font-family:'Poppins',sans-serif;background:#111;color:#fff;padding:20px;margin:0;}
.top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.dashboard-btn{background:#ffde59;padding:10px 20px;border-radius:5px;color:#111;font-weight:bold;text-decoration:none;}
.order-table{background:rgba(0,0,0,0.3);padding:15px;margin-bottom:30px;border-radius:8px;}
table{width:100%;border-collapse:collapse;margin-bottom:15px;}
th,td{padding:8px;border:1px solid #555;text-align:left;}
th{background:#222;color:#ffde59;}

/* Progress bar */
.progress-container{position:relative;width:100%;height:50px;margin:20px 0;}
.progress-line{position:absolute;top:50%;left:0;right:0;height:4px;background:#555;transform:translateY(-50%);}
.progress-fill{position:absolute;top:50%;left:0;height:4px;background:green;transform:translateY(-50%);}
.progress-point{position:absolute;top:50%;width:20px;height:20px;background:#555;border-radius:50%;transform:translate(-50%,-50%);}
.progress-point.completed{background:green;}
.progress-point.cancelled{background:red;}
.progress-label{position:absolute;top:60%;transform:translateX(-50%);font-size:12px;color:#fff;text-align:center;width:70px;}
.cancel-btn{background:#ff4c4c;padding:5px 10px;border:none;border-radius:4px;color:#fff;font-weight:bold;cursor:pointer;margin-top:10px;}
</style>
</head>
<body>

<div class="top-bar">
    <a class="dashboard-btn" href="dashboard.html?username=<?= urlencode($username) ?>">⬅ Back to Dashboard</a>
    <div>Welcome, <?= htmlspecialchars($username) ?></div>
</div>

<?php foreach($ordersData as $orderGroup):
    $payment = $orderGroup['payment'];
    $orders = $orderGroup['orders'];
    $status = $orders[0]['status'];
    $orderDate = new DateTime($orders[0]['order_date']);
    $deliveryDate = new DateTime($orders[0]['delivery_date']);
    $today = new DateTime();
    $totalDays = max(1,$orderDate->diff($deliveryDate)->days);
    $daysPassed = min($totalDays, $orderDate->diff($today)->days);
    $progressPercent = ($status=='Cancelled') ? 100 : ($daysPassed/$totalDays*100);
?>
<div class="order-table" id="order-<?= $payment['payment_id'] ?>">
    <h3>Payment ID: <?= $payment['payment_id'] ?></h3>

    <!-- Order Details -->
    <h4>📚 Order Details</h4>
    <table>
        <tr><th>#</th><th>Book</th><th>Author</th><th>Year</th><th>Pages</th><th>Price</th><th>Status</th></tr>
        <?php foreach($orders as $i=>$o): ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($o['book_name']) ?></td>
            <td><?= htmlspecialchars($o['author']) ?></td>
            <td><?= htmlspecialchars($o['year']) ?></td>
            <td><?= htmlspecialchars($o['pages']) ?></td>
            <td>₹<?= htmlspecialchars($o['price']) ?></td>
            <td><?= $o['status'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- Customer Details -->
    <h4>👤 Customer Details</h4>
    <table>
        <tr><th>Name</th><td><?= $payment['customer_name'] ?></td></tr>
        <tr><th>Email</th><td><?= $payment['email'] ?></td></tr>
        <tr><th>Phone</th><td><?= $payment['phone_number'] ?></td></tr>
        <tr><th>Gender</th><td><?= $payment['gender'] ?></td></tr>
    </table>

    <!-- Address -->
    <h4>🏠 Address</h4>
    <table>
        <tr><th>House</th><td><?= $payment['house'] ?></td></tr>
        <tr><th>Street</th><td><?= $payment['street'] ?></td></tr>
        <tr><th>Village</th><td><?= $payment['village'] ?></td></tr>
        <tr><th>City</th><td><?= $payment['city'] ?></td></tr>
        <tr><th>State</th><td><?= $payment['state'] ?></td></tr>
        <tr><th>Pincode</th><td><?= $payment['pincode'] ?></td></tr>
    </table>

    <!-- Payment Details -->
    <h4>💰 Payment Details</h4>
    <table>
        <tr><th>Amount</th><td>₹<?= $payment['total_amount'] ?></td></tr>
        <tr><th>Method</th><td><?= $payment['payment_method'] ?></td></tr>
        <tr><th>Status</th><td><?= $status ?></td></tr>
        <tr><th>Payment Date</th><td><?= $payment['payment_date'] ?></td></tr>
    </table>

    <!-- Dynamic Progress -->
    <h4>Progress</h4>
    <div class="progress-container">
        <div class="progress-line"></div>
        <div class="progress-fill" style="width:<?= $progressPercent ?>%;<?= ($status=='Cancelled')?'background:red;':'' ?>"></div>
        <?php
        $stages = ['Pending','Processing','Shipped','Delivered','Cancelled'];
        foreach($stages as $index=>$s):
            $left = ($index/($stagesCount = count($stages)-1))*100;
            $cls = ($status=='Cancelled' && $s=='Cancelled') ? 'cancelled' : (($progressPercent/100 >= $index/($stagesCount) ) ? 'completed' : '');
        ?>
        <div class="progress-point <?= $cls ?>" style="left:<?= $left ?>%"></div>
        <div class="progress-label" style="left:<?= $left ?>%"><?= $s ?></div>
        <?php endforeach; ?>
    </div>

    <?php if($status!='Cancelled' && $status!='Delivered'): ?>
        <button class="cancel-btn" data-payment="<?= $payment['payment_id'] ?>">Cancel Order</button>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<script>
// Cancel order
// Cancel order
document.querySelectorAll('.cancel-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const payment_id = btn.dataset.payment;
        if (!confirm("Are you sure to cancel this order?")) return;

        fetch('cancel_order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'payment_id=' + payment_id
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ Order cancelled and email sent.');

                // --- Instant progress bar update ---
                const container = document.getElementById('order-' + payment_id);
                if (container) {
                    // Change progress fill to red and 100%
                    const progressFill = container.querySelector('.progress-fill');
                    if (progressFill) {
                        progressFill.style.width = '100%';
                        progressFill.style.background = 'red';
                    }

                    // Change all points: only "Cancelled" point red, others grey
                    const points = container.querySelectorAll('.progress-point');
                    const labels = container.querySelectorAll('.progress-label');
                    points.forEach((p, i) => {
                        if (labels[i].innerText === 'Cancelled') {
                            p.classList.remove('completed');
                            p.classList.add('cancelled');
                        } else {
                            p.classList.remove('completed');
                            p.classList.remove('cancelled');
                            p.style.background = '#555';
                        }
                    });

                    // Update status in table
                    const statusCells = container.querySelectorAll('td');
                    statusCells.forEach(td => {
                        if(td.innerText !== 'Cancelled') td.innerText = 'Cancelled';
                    });

                    // Hide the cancel button
                    const cancelBtn = container.querySelector('.cancel-btn');
                    if(cancelBtn) cancelBtn.style.display = 'none';
                }

                // Reload the page after a short delay to get correct data from DB
                setTimeout(() => {
                    window.location.reload();
                }, 500); // 0.5 seconds delay
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('❌ Something went wrong.');
        });
    });
});


</script>
</body>
</html>
