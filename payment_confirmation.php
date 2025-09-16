<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['payment_data'] = $_POST;
}

$data = $_SESSION['payment_data'] ?? [];


// ---- Get username from POST or session ----
$username = $_POST['username'] ?? ($_SESSION['username'] ?? 'Guest');

// ---- All your existing POST values ----
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Payment Confirmation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    /* (All your styles kept exactly same!) */
    body {margin:0;padding:0;background:#111;color:#fff;font-family:Arial,sans-serif;}
    header.header{display:flex;align-items:center;justify-content:space-between;background:#222;padding:0.8rem 1rem;color:#fff;}
    .back-btn{background:#FFD700;color:#000;border:none;padding:0.7rem 1rem;border-radius:6px;font-weight:bold;cursor:pointer;}
    .logo{font-weight:bold;color:#FFD700;cursor:pointer;}
    .page-wrap{max-width:1200px;margin:1rem auto;padding:0 1rem;}
    .top-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem;}
    .timer{background:rgba(255,255,255,0.08);padding:0.8rem 1rem;border-radius:8px;font-weight:bold;border:1px solid rgba(255,255,255,0.15);}
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;}
    .panel{background:rgba(255,255,255,0.08);border-radius:10px;padding:1rem;min-height:520px;border:1px solid rgba(255,255,255,0.12);display:flex;flex-direction:column;}
    .panel h2{color:#FFD700;margin:0 0 0.8rem 0;padding-bottom:0.5rem;border-bottom:2px solid #FFD700;font-size:1.2rem;}
    .confirm-table{width:100%;border-collapse:collapse;margin-bottom:1rem;background:#333;border-radius:6px;overflow:hidden;}
    .confirm-table td{border:1px solid #555;padding:8px 12px;color:#fff;}
    .confirm-table tr:nth-child(even){background:#444;}
    .confirm-table tr td:first-child{font-weight:bold;width:40%;color:#FFD700;}
    .section{margin-bottom:1.2rem;}
    .form-group{margin-bottom:0.8rem;}
    label{display:block;margin-bottom:0.35rem;font-weight:bold;}
    input,select,button{border:none;border-radius:6px;outline:none;font-size:0.95rem;}
    input[type="text"],input[type="email"],input[type="tel"],input[type="number"],select{width:100%;padding:0.6rem;}
    .btn{background:#FFD700;color:#000;padding:0.7rem 1rem;border-radius:6px;cursor:pointer;font-weight:bold;}
    .btn.secondary{background:#999;color:#000;}
    .btn.block{width:100%;}
    .btn:disabled{opacity:0.6;cursor:not-allowed;}
    .pm-block{background:rgba(0,0,0,0.35);border-radius:8px;padding:0.8rem;margin-bottom:1rem;border:1px solid rgba(255,255,255,0.12);}
    .inline{display:flex;gap:0.5rem;align-items:center;}
    .help{font-size:0.85rem;color:#bbb;margin-top:0.3rem;}
    .overlay{position:fixed;inset:0;background:rgba(0,0,0,0.7);display:none;align-items:center;justify-content:center;z-index:1000;}
    .modal{background:#222;color:#fff;border-radius:10px;width:min(92vw,520px);padding:1.2rem 1rem;border:1px solid rgba(255,255,255,0.15);}
    .modal h3{margin-top:0;color:#FFD700;}
    .modal .actions{display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1rem;}
    .qr-box{width:240px;height:240px;background:#fff;display:grid;place-items:center;margin:0.6rem auto;border-radius:8px;overflow:hidden;}
    .qr-svg{width:100%;height:100%;}
    @media(max-width:900px){.grid-2{grid-template-columns:1fr;}.panel{min-height:auto;}}
  </style>
</head>
<body>
<header class="header">
  <button class="back-btn" id="backBtn">← Back</button>
  <div class="logo"></div>
  <div class="user-info">Welcome, <?= htmlspecialchars($username) ?></div>
</header>
<div class="page-wrap">
  <div class="top-row">
    <h1 style="margin:0;font-size:1.1rem;color:#eee;">Payment Confirmation</h1>
    <div class="timer">Session expires in: <span id="countdown">08:00</span></div>
    <form id="paymentForm" method="POST" action="payment_success.php">
      <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
      <input type="hidden" name="booksData" value='<?= htmlspecialchars(json_encode($books)) ?>'>
      <input type="hidden" name="travellerName" value="<?= htmlspecialchars($travellerName) ?>">
      <input type="hidden" name="travellerEmail" value="<?= htmlspecialchars($travellerEmail) ?>">
      <input type="hidden" name="travellerPhone" value="<?= htmlspecialchars($travellerPhone) ?>">
      <input type="hidden" name="travellerGender" value="<?= htmlspecialchars($travellerGender) ?>">
      <input type="hidden" name="houseNo" value="<?= htmlspecialchars($houseNo) ?>">
      <input type="hidden" name="street" value="<?= htmlspecialchars($street) ?>">
      <input type="hidden" name="village" value="<?= htmlspecialchars($village) ?>">
      <input type="hidden" name="city" value="<?= htmlspecialchars($city) ?>">
      <input type="hidden" name="state" value="<?= htmlspecialchars($state) ?>">
      <input type="hidden" name="pincode" value="<?= htmlspecialchars($pincode) ?>">
      <input type="hidden" name="baseAmount" value="<?= htmlspecialchars($baseAmount) ?>">
      <input type="hidden" name="gstAmount" value="<?= htmlspecialchars($gstAmount) ?>">
      <input type="hidden" name="discountAmount" value="<?= htmlspecialchars($discountAmount) ?>">
      <input type="hidden" name="finalTotal" value="<?= htmlspecialchars($finalTotal) ?>">
      <input type="hidden" name="paymentMethod" id="paymentMethod" value="">
    </form>
  </div>

  <div class="grid-2">
    <!-- LEFT: Order Summary -->
    <section class="panel">
      <h2>Order Details</h2>
      <div class="section">
        <h3 style="margin:0 0 0.4rem 0;color:#e7e7e7;font-size:1rem;">📚 Book</h3>
        <table class="confirm-table">
          <tr><td>#</td><td>Book</td><td>Author</td><td>Price</td><td>Qty</td><td>Total</td></tr>
          <?php if (!empty($books)): foreach ($books as $i=>$b): ?>
          <tr>
            <td><?= $i+1 ?></td><td><?= htmlspecialchars($b['book_name']) ?></td>
            <td><?= htmlspecialchars($b['author']) ?></td><td>₹<?= number_format($b['price'],2) ?></td>
            <td><?= htmlspecialchars($b['quantity']) ?></td><td>₹<?= number_format($b['price']*$b['quantity'],2) ?></td>
          </tr>
          <?php endforeach; else: ?><tr><td colspan="6">No book data</td></tr><?php endif; ?>
        </table>
      </div>
      <div class="section">
        <h3 style="margin:0 0 0.4rem 0;color:#e7e7e7;font-size:1rem;">👤 Traveller</h3>
        <table class="confirm-table">
          <tr><td>Name</td><td><?= htmlspecialchars($travellerName) ?></td></tr>
          <tr><td>Email</td><td><?= htmlspecialchars($travellerEmail) ?></td></tr>
          <tr><td>Phone</td><td><?= htmlspecialchars($travellerPhone) ?></td></tr>
          <tr><td>Gender</td><td><?= htmlspecialchars($travellerGender) ?></td></tr>
        </table>
      </div>
      <div class="section">
        <h3 style="margin:0 0 0.4rem 0;color:#e7e7e7;font-size:1rem;">💳 Payment</h3>
        <table class="confirm-table">
          <tr><td>Base</td><td>₹<?= htmlspecialchars($baseAmount) ?></td></tr>
          <tr><td>GST</td><td>₹<?= htmlspecialchars($gstAmount) ?></td></tr>
          <tr><td>Discount</td><td>₹<?= htmlspecialchars($discountAmount) ?></td></tr>
          <tr><td>Total</td><td>₹<?= htmlspecialchars($finalTotal) ?></td></tr>
        </table>
      </div>
      <div class="section">
        <h3 style="margin:0 0 0.4rem 0;color:#e7e7e7;font-size:1rem;">📦 Address</h3>
        <table class="confirm-table">
          <tr><td>House</td><td><?= htmlspecialchars($houseNo) ?></td></tr>
          <tr><td>Street</td><td><?= htmlspecialchars($street) ?></td></tr>
          <tr><td>Village</td><td><?= htmlspecialchars($village) ?></td></tr>
          <tr><td>City</td><td><?= htmlspecialchars($city) ?></td></tr>
          <tr><td>State</td><td><?= htmlspecialchars($state) ?></td></tr>
          <tr><td>Pincode</td><td><?= htmlspecialchars($pincode) ?></td></tr>
        </table>
      </div>
    </section>

    <!-- RIGHT: Payment -->
    <section class="panel">
      <h2>Payment Methods</h2>
      <div class="pm-block">
        <h3 style="margin:0 0 0.6rem 0;font-size:1rem;">UPI</h3>
        <div class="form-group">
          <label for="upiId">UPI ID</label>
          <input type="text" id="upiId" placeholder="yourname@bank"/>
          <div class="help">Format example: name@bank</div>
        </div>
        <div class="inline">
          <button class="btn" type="button" id="btnVerifyUpi">Verify UPI</button>
          <button class="btn" type="button" id="btnShowQR">Show QR</button>
        </div>
        <div id="upiStatus" class="help" style="margin-top:0.5rem;"></div>
      </div>
      <div class="pm-block">
        <h3 style="margin:0 0 0.6rem 0;font-size:1rem;">Card</h3>
        <div class="form-group"><label for="cardName">Card Holder Name</label><input type="text" id="cardName"></div>
        <div class="form-group"><label for="cardNumber">Card Number</label><input type="text" id="cardNumber" maxlength="19"></div>
        <div class="inline" style="gap:0.8rem;">
          <div class="form-group" style="flex:1;"><label for="expMonth">Month</label><select id="expMonth"><option value="">MM</option></select></div>
          <div class="form-group" style="flex:1;"><label for="expYear">Year</label><select id="expYear"><option value="">YYYY</option></select></div>
          <div class="form-group" style="flex:1;"><label for="cvv">CVV</label><input type="password" id="cvv" maxlength="4"></div>
        </div>
      </div>
      <button class="btn block" id="btnProceed" disabled>Proceed to Pay</button>
    </section>
  </div>
</div>

<!-- QR POPUP -->
<div class="overlay" id="qrOverlay">
  <div class="modal">
    <h3>Scan & Pay</h3>
    <div class="qr-box">
     <div class="qr-box">
  <img id="qrImage" src="images/qr.png" alt="QR Code" style="width:100%;height:100%;" />
</div>
    </div>
    <p style="text-align:center;">Time left: <span id="qrTimer">300</span> sec</p>
    <div class="actions">
      <button class="btn secondary" type="button" id="qrBack">Back</button>
      <button class="btn" type="button" id="qrOk">OK</button>
    </div>
  </div>
</div>

<!-- Back Warning -->
<div class="overlay" id="backOverlay">
  <div class="modal">
    <h3>Leave Payment?</h3>
    <p>If you go back now, your order will be cancelled.</p>
    <div class="actions">
      <button class="btn secondary" id="stayHere">Back</button>
      <button class="btn" id="leaveNow">OK</button>
    </div>
  </div>
</div>

<script>
  let isQrScanned = false;
  let isUpiVerified = false;

  const btnProceed = document.getElementById('btnProceed');
  const countdownEl = document.getElementById('countdown');
  const paymentForm = document.getElementById('paymentForm');
  const paymentMethodInput = document.getElementById('paymentMethod');

  // ===== BACK BUTTON =====
  history.pushState(null, null, location.href);
  window.addEventListener('popstate', showBackWarning);
  document.getElementById('backBtn').addEventListener('click', showBackWarning);

  function showBackWarning() {
    document.getElementById('backOverlay').style.display = 'flex';
    history.pushState(null, null, location.href);
  }

  document.getElementById('stayHere').onclick = () => {
    document.getElementById('backOverlay').style.display = 'none';
  };

  document.getElementById('leaveNow').onclick = () => {
    paymentForm.action = 'payment_failed.php';
    paymentForm.submit();
  };

  // ===== SESSION TIMER =====
  let sessionExpiry = Date.now() + 8 * 60 * 1000; // 8 minutes
  let timerInterval;

  function startTimer() {
    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
      const now = Date.now();
      const timeLeft = Math.floor((sessionExpiry - now) / 1000);
      if (timeLeft <= 0) {
        clearInterval(timerInterval);
        alert("⏰ Time expired! Payment failed.");
        paymentForm.action = 'payment_failed.php';
        paymentForm.submit();
        return;
      }
      const mins = String(Math.floor(timeLeft / 60)).padStart(2, '0');
      const secs = String(timeLeft % 60).padStart(2, '0');
      countdownEl.textContent = `${mins}:${secs}`;
    }, 1000);
  }
  function stopTimer() { clearInterval(timerInterval); }
  startTimer();

  // ===== VERIFY UPI =====
  document.getElementById('btnVerifyUpi').addEventListener('click', () => {
    const upi = document.getElementById('upiId').value.trim();
    const statusEl = document.getElementById('upiStatus');
    const regex = /^[\w.-]+@[\w.-]+$/;
    if (!upi || !regex.test(upi)) {
      statusEl.textContent = 'Invalid UPI ID!';
      statusEl.style.color = 'red';
      return;
    }
    statusEl.textContent = '✅ UPI Verified';
    statusEl.style.color = 'lightgreen';
    isUpiVerified = true;
    paymentMethodInput.value = 'UPI';
    btnProceed.disabled = false;
  });

  // ===== QR POPUP =====
  const qrOverlay = document.getElementById('qrOverlay');
  const qrTimerEl = document.getElementById('qrTimer');
  let qrInterval;

  document.getElementById('btnShowQR').onclick = () => {
    qrOverlay.style.display = 'flex';
    let timeLeft = 300; // 5 minutes
    qrTimerEl.textContent = timeLeft;
    clearInterval(qrInterval);
    qrInterval = setInterval(() => {
      timeLeft--;
      qrTimerEl.textContent = timeLeft;
      if (timeLeft <= 0) {
        clearInterval(qrInterval);
        alert("⏰ QR time expired! Payment failed.");
        paymentForm.action = 'payment_failed.php';
        paymentForm.submit();
      }
    }, 1000);
  };

  document.getElementById('qrBack').onclick = () => {
    qrOverlay.style.display = 'none';
    clearInterval(qrInterval);
  };

  document.getElementById('qrOk').onclick = () => {
    clearInterval(qrInterval);
    qrOverlay.style.display = 'none';
    isQrScanned = true;
    paymentMethodInput.value = 'QR';
    stopTimer();
    paymentForm.submit();
  };

  // ===== CARD PAYMENT CHECK =====
  function cardDetailsFilled() {
    const name = document.getElementById('cardName').value.trim();
    const number = document.getElementById('cardNumber').value.trim();
    const month = document.getElementById('expMonth').value.trim();
    const year = document.getElementById('expYear').value.trim();
    const cvv = document.getElementById('cvv').value.trim();
    return name && number && month && year && cvv;
  }

  // ===== PROCEED BUTTON =====
  btnProceed.addEventListener('click', () => {
    if (isQrScanned || isUpiVerified || cardDetailsFilled()) {
      paymentMethodInput.value = isQrScanned ? 'QR' : (isUpiVerified ? 'UPI' : 'Card');
      stopTimer();
      paymentForm.submit();
    } else {
      alert('Please verify UPI, scan QR, or enter card details!');
    }
  });

  // Enable Proceed button when card details filled
  document.querySelectorAll("input, select").forEach(el => {
    el.addEventListener('input', () => {
      btnProceed.disabled = !(isQrScanned || isUpiVerified || cardDetailsFilled());
    });
  });
</script>

</body>
</html>
