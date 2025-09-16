<?php
// Get POST data from previous page
$books = $_POST['books'] ?? [];
$username = $_POST['username'] ?? '';
$cart_total = $_POST['cart_total'] ?? '0';

// Make sure $books is decoded properly
if (is_string($books)) {
    $books = json_decode($books, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Details</title>
<link rel="stylesheet" href="css/style.css">
<style>
body {
  font-family: Arial, sans-serif;
  background: #111;
  color: #fff;
  margin: 0;
  padding: 0;
}
.popup table {
  width: 100%;
  border-collapse: collapse;
  margin: 0.5rem 0;
}
.popup th, .popup td {
  border: 1px solid #555;
  padding: 6px;
  text-align: left;
}
.popup th {
  background: #333;
  color: #FFD700;
}

main {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1rem;
  padding: 1rem;
}
.container {
  padding: 1rem;
  background: rgba(255,255,255,0.08);
  border-radius: 8px;
}
h2 { color: #FFD700; margin-bottom: 0.5rem; }
.form-group { margin-bottom: 1rem; }
label { display: block; margin-bottom: 0.3rem; font-weight: bold; }
input, select { width: 100%; padding: 0.5rem; border-radius: 6px; border: none; outline: none; }
.btn { background: #FFD700; color: #000; padding: 0.5rem 1rem; border-radius: 6px; border: none; cursor: pointer; margin-top: 0.5rem; }
.btn:hover { background: #e6c200; }
.cart-total { margin-top: 1rem; padding: 1rem; background: rgba(255,255,255,0.2); border-radius: 6px; text-align: center; }
.popup-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.7); display:none; justify-content:center; align-items:center; z-index:1000; }
.popup { background:#222; padding:2rem; border-radius:10px; max-width:600px; width:90%; color:#fff; }
.popup h3 { color:#FFD700; margin-bottom:1rem; }
.popup button { margin:0.5rem; padding:0.6rem 1rem; border-radius:6px; border:none; cursor:pointer; }
.popup .btn-back { background:#ccc; }
.popup .btn-pay { background:#FFD700; }
.book-row { margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #555; }
.book-row p { margin: 0.2rem 0; }
.book-row button { margin:0 0.3rem; padding:0.2rem 0.5rem; }

/* Make sure each container scrolls if content is long */
.container { max-height: 500px; overflow-y: auto; }
</style>
</head>
<body>
<header class="header" style="padding:1rem; display:flex; align-items:center; gap:1rem;">
<button class="back-btn" onclick="goBack()">← Back</button>

  <div class="logo" onclick="goToDashboard()">📚 BookStore</div>
</header>

<main>
  <!-- 1. Book Details -->
  <div class="container" id="bookDetails">
    <h2>Books in Your Order</h2>
  </div>

  <!-- 2. Traveller Details -->
  <div class="container">
    <h2>Traveller Details</h2>
    <div class="form-group">
      <label for="travellerName">Full Name</label>
      <input type="text" id="travellerName" required>
    </div>
    <div class="form-group">
      <label for="travellerEmail">Email</label>
      <input type="email" id="travellerEmail" required>
    </div>
    <div class="form-group">
      <label for="travellerPhone">Phone</label>
      <input type="tel" id="travellerPhone" maxlength="10" required>
    </div>
    <div class="form-group">
      <label for="travellerGender">Gender</label>
      <select id="travellerGender" required>
        <option value="">Select</option>
        <option>Male</option>
        <option>Female</option>
        <option>Other</option>
      </select>
    </div>
  </div>

  <!-- 3. Payment Details -->
  <div class="container">
    <h2>Payment Details</h2>
    <div class="cart-total">
      <p>Base Amount: ₹<span id="baseAmount">0.00</span></p>
      <p>GST (5%): ₹<span id="gstAmount">0.00</span></p>
      <p>Discount: ₹<span id="discountAmount">0.00</span></p>
      <p><strong>Final Total: ₹<span id="finalTotal">0.00</span></strong></p>
      <div class="form-group">
        <label for="couponCode">Apply Coupon</label>
        <input type="text" id="couponCode" placeholder="Enter coupon code">
        <button class="btn" type="button" onclick="applyCoupon()">Apply</button>
      </div>
      <button class="btn" onclick="showConfirmation()">Proceed to Pay</button>
    </div>
  </div>

  <!-- 4. Delivery Address -->
  <div class="container">
    <h2>Delivery Address</h2>
    <div class="form-group"><label>House Number</label><input id="houseNo" required></div>
    <div class="form-group"><label>Street / Apartment</label><input id="street" required></div>
    <div class="form-group"><label>Village</label><input id="village" required></div>
    <div class="form-group"><label>City / Town</label><input id="city" required></div>
    <div class="form-group"><label>State</label><input id="state" required></div>
    <div class="form-group"><label>Pincode</label><input id="pincode" maxlength="6" required></div>
  </div>
</main>

<!-- Hidden Form & Popup (unchanged) -->
<form id="paymentForm" action="payment_confirmation.php" method="POST" style="display:none;">
  <input type="hidden" name="username" id="formUsername">
  <input type="hidden" name="booksData" id="formBooksData">
  <input type="hidden" name="travellerName" id="formTravellerName">
  <input type="hidden" name="travellerEmail" id="formTravellerEmail">
  <input type="hidden" name="travellerPhone" id="formTravellerPhone">
  <input type="hidden" name="travellerGender" id="formTravellerGender">
  <input type="hidden" name="houseNo" id="formHouseNo">
  <input type="hidden" name="street" id="formStreet">
  <input type="hidden" name="village" id="formVillage">
  <input type="hidden" name="city" id="formCity">
  <input type="hidden" name="state" id="formState">
  <input type="hidden" name="pincode" id="formPincode">
  <input type="hidden" name="baseAmount" id="formBaseAmount">
  <input type="hidden" name="gstAmount" id="formGstAmount">
  <input type="hidden" name="discountAmount" id="formDiscountAmount">
  <input type="hidden" name="finalTotal" id="formFinalTotal">
</form>

<div class="popup-overlay" id="confirmationPopup">
  <div class="popup">
    <h3>Confirm Payment Details</h3>
    <div id="confirmationContent"></div>
    <div style="text-align:center; margin-top:1rem;">
      <button class="btn-back" onclick="closePopup()">Back</button>
      <button class="btn-pay" onclick="finalPay()">Pay</button>
    </div>
  </div>
</div>

<script>
let username = null;
let books = [];
let discountAmount = 0;

document.addEventListener("DOMContentLoaded", function() {
  books = <?php echo json_encode($books); ?>;
  username = "<?php echo htmlspecialchars($username, ENT_QUOTES); ?>";
  const cartTotal = "<?php echo htmlspecialchars($cart_total, ENT_QUOTES); ?>";

  if (!books.length) {
    alert("No cart data found!");
    return;
  }

  renderBooks();
  document.getElementById('baseAmount').textContent = parseFloat(cartTotal).toFixed(2);
  calculateTotal();
});

// ✅ Function to get POSTed data



function renderBooks() {
  const container = document.getElementById('bookDetails');
  container.innerHTML = `<h2>Books in Your Order</h2>`;

  books.forEach((book, i) => {
    const div = document.createElement('div');
    div.classList.add('book-row');
    div.innerHTML = `
      <p>
        <input type="checkbox" class="book-select" data-index="${i}" checked>
        <strong>Title:</strong> ${book.book_name}
      </p>
      <p><strong>Author:</strong> ${book.author}</p>
      <p><strong>Year:</strong> ${book.year}</p>
      <p><strong>Pages:</strong> ${book.pages}</p>
      <p><strong>Description:</strong> ${book.description}</p>
      <p><strong>Price:</strong> ₹${book.price}</p>
      <p>
        <strong>Quantity:</strong>
        <button class="btn-dec" data-index="${i}">➖</button>
        <span id="qty-${i}">${book.quantity}</span>
        <button class="btn-inc" data-index="${i}">➕</button>
      </p>
      <hr>
    `;
    container.appendChild(div);
  });

  // Checkbox change updates total
  document.querySelectorAll('.book-select').forEach(cb => {
    cb.addEventListener('change', calculateTotal);
  });

  // Increment / Decrement quantity
  container.querySelectorAll('.btn-inc').forEach(btn => {
    btn.addEventListener('click', e => {
      const i = e.target.dataset.index;
      books[i].quantity++;
      document.getElementById(`qty-${i}`).textContent = books[i].quantity;
      calculateTotal();
    });
  });

  container.querySelectorAll('.btn-dec').forEach(btn => {
    btn.addEventListener('click', e => {
      const i = e.target.dataset.index;
      if (books[i].quantity > 1) {
        books[i].quantity--;
        document.getElementById(`qty-${i}`).textContent = books[i].quantity;
        calculateTotal();
      }
    });
  });

  calculateTotal(); // initial total
}

function calculateTotal() {
  let base = 0;
  document.querySelectorAll('.book-select:checked').forEach(cb => {
    const book = books[cb.dataset.index];
    base += book.price * book.quantity;
  });

  let gst = base * 0.05;
  let finalTotal = base + gst - discountAmount;

  document.getElementById('baseAmount').textContent = base.toFixed(2);
  document.getElementById('gstAmount').textContent = gst.toFixed(2);
  document.getElementById('discountAmount').textContent = discountAmount.toFixed(2);
  document.getElementById('finalTotal').textContent = finalTotal.toFixed(2);
}

function applyCoupon() {
  const code = document.getElementById('couponCode').value.trim().toLowerCase();
  let base = Array.from(document.querySelectorAll('.book-select:checked'))
                  .reduce((sum, cb) => {
                    const book = books[cb.dataset.index];
                    return sum + book.price * book.quantity;
                  }, 0);

  let discount = 0;
  let valid = false;

  // Pattern 1: y22cm001–y22cm216
  let match = code.match(/^y22cm(\d{3})$/i);
  if (match && parseInt(match[1]) >= 1 && parseInt(match[1]) <= 216) {
    discount = base * 0.3;
    valid = true;
  }

  // Pattern 2: y22cd001–y22cd215
  match = code.match(/^y22cd(\d{3})$/i);
  if (match && parseInt(match[1]) >= 1 && parseInt(match[1]) <= 215) {
    discount = base * 0.3;
    valid = true;
  }

  // Pattern 3: y22cb001–y22cb072
  match = code.match(/^y22cb(\d{3})$/i);
  if (match && parseInt(match[1]) >= 1 && parseInt(match[1]) <= 72) {
    discount = base * 0.3;
    valid = true;
  }

  if (!valid && code !== "") {
    alert("❌ Invalid coupon code!");
  }

  discountAmount = discount;
  calculateTotal();
}

function showConfirmation() {
  const traveller = {
    name: document.getElementById('travellerName').value,
    email: document.getElementById('travellerEmail').value,
    phone: document.getElementById('travellerPhone').value,
    gender: document.getElementById('travellerGender').value
  };
  const address = {
    houseNo: document.getElementById('houseNo').value,
    street: document.getElementById('street').value,
    village: document.getElementById('village').value,
    city: document.getElementById('city').value,
    state: document.getElementById('state').value,
    pincode: document.getElementById('pincode').value
  };
  const selectedBooks = books.filter((b, i) => document.querySelector(`.book-select[data-index="${i}"]`).checked);

  if (!selectedBooks.length) { alert("Select at least one book!"); return; }

  let bookTable = `<table><tr><th>#</th><th>Book</th><th>Author</th><th>Price</th><th>Qty</th><th>Total</th></tr>`;
  selectedBooks.forEach((b, i) => {
    bookTable += `<tr><td>${i+1}</td><td>${b.book_name}</td><td>${b.author}</td><td>₹${b.price}</td><td>${b.quantity}</td><td>₹${(b.price*b.quantity).toFixed(2)}</td></tr>`;
  });
  bookTable += "</table>";

  let payTable = `<table><tr><th>Base</th><th>GST</th><th>Discount</th><th>Total</th></tr>
    <tr><td>₹${document.getElementById('baseAmount').textContent}</td>
    <td>₹${document.getElementById('gstAmount').textContent}</td>
    <td>₹${document.getElementById('discountAmount').textContent}</td>
    <td>₹${document.getElementById('finalTotal').textContent}</td></tr></table>`;

  let addressTable = `<table><tr><th>House</th><th>Street</th><th>Village</th><th>City</th><th>State</th><th>Pincode</th></tr>
    <tr><td>${address.houseNo}</td><td>${address.street}</td><td>${address.village}</td><td>${address.city}</td><td>${address.state}</td><td>${address.pincode}</td></tr></table>`;

  let customerTable = `<table><tr><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th></tr>
    <tr><td>${traveller.name}</td><td>${traveller.email}</td><td>${traveller.phone}</td><td>${traveller.gender}</td></tr></table>`;

  document.getElementById('confirmationContent').innerHTML = 
    `<h4>📚 Books</h4>${bookTable}<h4>💳 Payment</h4>${payTable}<h4>📦 Address</h4>${addressTable}<h4>👤 Customer</h4>${customerTable}`;
  document.getElementById('confirmationPopup').style.display = 'flex';
}
function closePopup() { document.getElementById('confirmationPopup').style.display = 'none'; }

function finalPay() {
  // Basic validation
  if (!document.getElementById('travellerName').value ||
      !document.getElementById('travellerEmail').value ||
      !document.getElementById('travellerPhone').value ||
      !document.getElementById('houseNo').value ||
      !document.getElementById('street').value ||
      !document.getElementById('city').value ||
      !document.getElementById('state').value ||
      !document.getElementById('pincode').value) {
    alert("Please fill all fields before proceeding!");
    return;
  }

const selectedBooks = [];
document.querySelectorAll('.book-select:checked').forEach(cb => {
  selectedBooks.push(books[cb.dataset.index]);
});

if (selectedBooks.length === 0) {
  alert("Please select at least one book!");
  return;
}

// Always stringify a valid array
document.getElementById('formBooksData').value = JSON.stringify(selectedBooks);


  // Populate hidden form
  document.getElementById('formUsername').value = username || "";
document.getElementById('formBooksData').value = JSON.stringify(selectedBooks);
  document.getElementById('formTravellerName').value = document.getElementById('travellerName').value;
  document.getElementById('formTravellerEmail').value = document.getElementById('travellerEmail').value;
  document.getElementById('formTravellerPhone').value = document.getElementById('travellerPhone').value;
  document.getElementById('formTravellerGender').value = document.getElementById('travellerGender').value;
  document.getElementById('formHouseNo').value = document.getElementById('houseNo').value;
  document.getElementById('formStreet').value = document.getElementById('street').value;
  document.getElementById('formVillage').value = document.getElementById('village').value;
  document.getElementById('formCity').value = document.getElementById('city').value;
  document.getElementById('formState').value = document.getElementById('state').value;
  document.getElementById('formPincode').value = document.getElementById('pincode').value;
  document.getElementById('formBaseAmount').value = document.getElementById('baseAmount').textContent;
  document.getElementById('formGstAmount').value = document.getElementById('gstAmount').textContent;
  document.getElementById('formDiscountAmount').value = document.getElementById('discountAmount').textContent;
  document.getElementById('formFinalTotal').value = document.getElementById('finalTotal').textContent;

  document.getElementById("paymentForm").submit();
}

function goBack() {
  const encodedUsername = encodeURIComponent(username || '');
  window.location.href = `cart.html?username=${encodedUsername}`;
}
function goToDashboard(){
 const encodedUsername = encodeURIComponent(username || '');
  window.location.href = `dashboard.html?username=${encodedUsername}`;
}

</script>

</body>
</html>
