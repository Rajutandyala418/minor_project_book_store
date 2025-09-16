// ---------------------- CART FUNCTIONS ----------------------
function getCart() {
    return JSON.parse(localStorage.getItem('cart')) || [];
}

function saveCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
}

function updateCartCount() {
    const cart = getCart();
    const countEl = document.getElementById('cartCount');
    if (countEl) {
        let totalItems = 0;
        cart.forEach(item => totalItems += item.quantity);
        countEl.innerText = totalItems;
    }
}

function addToCart(bookId, category = null) {
    let cart = getCart();
    let book = findBookById(bookId, category);

    if (!book) return;

    let existing = cart.find(item => item.id === book.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({ ...book, quantity: 1 });
    }

    saveCart(cart);
    alert(`${book.title} added to cart!`);
}

function addToCartFromCategory(bookId, category) {
    addToCart(bookId, category);
}

// Find book by ID in specific category or all categories
function findBookById(bookId, category = null) {
    if (category && booksData[category]) {
        return booksData[category].find(b => b.id === bookId) || null;
    }

    for (let cat in booksData) {
        let book = booksData[cat].find(b => b.id === bookId);
        if (book) return book;
    }
    return null;
}

// ---------------------- BUY NOW FUNCTION ----------------------
function buyNow(bookId, category = null) {
    const book = findBookById(bookId, category);
    if (!book) return;

    sessionStorage.setItem('buyNowItem', JSON.stringify(book));
    sessionStorage.removeItem('cartPayment');

    window.location.href = 'payment.html';
}

// ---------------------- DISPLAY CART ----------------------
function displayCart(withSelection = false) {
    const cartContainer = document.getElementById('cartItems');
    const totalEl = document.getElementById('totalAmount');
    if (!cartContainer || !totalEl) return;

    const cart = getCart();
    cartContainer.innerHTML = "";

    if (cart.length === 0) {
        cartContainer.innerHTML = "<p>Your cart is empty.</p>";
        totalEl.innerText = "0.00";
        return;
    }

    cart.forEach(item => {
        const bookDiv = document.createElement('div');
        bookDiv.classList.add('cart-item');
        bookDiv.innerHTML = `
            ${withSelection ? `<input type="checkbox" class="cart-select" data-id="${item.id}" checked>` : ''}
            <img src="${item.image}" alt="${item.title}">
            <div class="cart-item-details">
                <h4>${item.title}</h4>
                <p><strong>Author:</strong> ${item.author}</p>
                <p><strong>Year:</strong> ${item.year} | <strong>Pages:</strong> ${item.pages}</p>
                <p><strong>Price:</strong> ₹${item.price}</p>
                <div class="quantity-controls">
                    <button onclick="changeQuantity(${item.id}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button onclick="changeQuantity(${item.id}, 1)">+</button>
                </div>
                <button class="btn btn-danger" onclick="removeFromCart(${item.id})">Remove</button>
            </div>
        `;
        cartContainer.appendChild(bookDiv);
    });

    updateSelectedTotal();

    document.querySelectorAll('.cart-select').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const allSelected = Array.from(document.querySelectorAll('.cart-select')).every(cb => cb.checked);
            const selectAll = document.getElementById('selectAll');
            if (selectAll) selectAll.checked = allSelected;
            updateSelectedTotal();
        });
    });
}

// ---------------------- SELECT ALL FUNCTION ----------------------
function setupSelectAll() {
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.cart-select');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateSelectedTotal();
        });
    }
}

function updateSelectedTotal() {
    const cart = getCart();
    const checkboxes = document.querySelectorAll('.cart-select');
    let total = 0;

    if (checkboxes.length === 0) {
        cart.forEach(item => total += item.price * item.quantity);
    } else {
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const itemId = parseInt(checkbox.dataset.id);
                const item = cart.find(b => b.id === itemId);
                if (item) total += item.price * item.quantity;
            }
        });
    }

    const totalEl = document.getElementById('totalAmount');
    if (totalEl) totalEl.innerText = total.toFixed(2);
}

// ---------------------- QUANTITY CHANGE ----------------------
function changeQuantity(bookId, change) {
    let cart = getCart();
    let item = cart.find(b => b.id === bookId);
    if (!item) return;

    item.quantity += change;
    if (item.quantity <= 0) {
        cart = cart.filter(b => b.id !== bookId);
    }

    saveCart(cart);
    displayCart(true);
}

// ---------------------- REMOVE ITEM ----------------------
function removeFromCart(bookId) {
    let cart = getCart().filter(item => item.id !== bookId);
    saveCart(cart);
    displayCart(true);
}

// ---------------------- PROCEED TO PAYMENT ----------------------
function proceedToPayment() {
    const cart = getCart();
    const checkboxes = document.querySelectorAll('.cart-select');
    const selectedItems = [];

    if (checkboxes.length > 0) {
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const itemId = parseInt(checkbox.dataset.id);
                const item = cart.find(b => b.id === itemId);
                if (item) selectedItems.push(item);
            }
        });
    } else {
        selectedItems.push(...cart);
    }

    if (selectedItems.length === 0) {
        alert("Please select at least one book to proceed.");
        return;
    }

    sessionStorage.setItem('cartPayment', 'true');
    localStorage.setItem('cart', JSON.stringify(selectedItems));
    sessionStorage.removeItem('buyNowItem');

    window.location.href = 'payment.html';
}

// ---------------------- PAYMENT PAGE ----------------------
document.addEventListener("DOMContentLoaded", function () {
    if (window.location.pathname.includes("payment.html")) {
        const buyNowItem = JSON.parse(sessionStorage.getItem('buyNowItem'));
        const totalEl = document.getElementById('paymentTotal');

        if (buyNowItem) {
            totalEl.innerText = buyNowItem.price.toFixed(2);
        } else {
            const cart = getCart();
            let total = 0;
            cart.forEach(item => total += item.price * item.quantity);
            if (totalEl) totalEl.innerText = total.toFixed(2);
        }

        const paymentForm = document.getElementById('paymentForm');
        if (paymentForm) {
            paymentForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (validatePaymentForm()) {
                    let cartData = [];
                    let totalPrice = 0;

                 if (buyNowItem) {
    cartData = [{ ...buyNowItem, quantity: 1 }];
    totalPrice = buyNowItem.price;
}
else {
                        cartData = getCart();
                        totalPrice = cartData.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    }

                    const buyerDetails = {
                        name: document.getElementById('customerName').value.trim(),
                        email: document.getElementById('customerEmail').value.trim(),
                        phone: document.getElementById('customerPhone').value.trim(),
                        street: document.getElementById('street').value.trim(),
                        area: document.getElementById('area').value.trim(),
                        district: document.getElementById('district').value.trim(),
                        state: document.getElementById('state').value.trim(),
                        pincode: document.getElementById('pincode').value.trim(),
                        total: totalPrice.toFixed(2),
                        cart: cartData
                    };

                    localStorage.setItem('orderDetails', JSON.stringify(buyerDetails));
                    sessionStorage.removeItem('buyNowItem');
                    sessionStorage.removeItem('cartPayment');
                    window.location.href = 'payment-success.html';
                }
            });
        }
    }

    if (window.location.pathname.includes("payment-success.html")) {
        loadPaymentSuccess();
    }
});

// ---------------------- VALIDATION ----------------------
function validatePaymentForm() {
    const name = document.getElementById('customerName').value.trim();
    const email = document.getElementById('customerEmail').value.trim();
    const phone = document.getElementById('customerPhone').value.trim();
    const street = document.getElementById('street').value.trim();
    const area = document.getElementById('area').value.trim();
    const district = document.getElementById('district').value.trim();
    const state = document.getElementById('state').value.trim();
    const pincode = document.getElementById('pincode').value.trim();

    if (!name || !email || !phone || !street || !area || !district || !state || !pincode) {
        alert("Please fill in all required fields.");
        return false;
    }

    if (!/^[a-zA-Z ]+$/.test(name)) {
        alert("Name should only contain letters and spaces.");
        return false;
    }

    if (!/^\S+@\S+\.\S+$/.test(email)) {
        alert("Please enter a valid email address.");
        return false;
    }

    if (!/^[0-9]{10}$/.test(phone)) {
        alert("Please enter a valid 10-digit phone number.");
        return false;
    }

    if (!/^[0-9]{6}$/.test(pincode)) {
        alert("Please enter a valid 6-digit pincode.");
        return false;
    }

    return true;
}

// ---------------------- SUCCESS PAGE ----------------------
function loadPaymentSuccess() {
    const orderDetails = JSON.parse(localStorage.getItem('orderDetails') || '{}');
    const orderedBooksList = document.getElementById('orderedBooksList');

    if (orderedBooksList && orderDetails.cart) {
        orderedBooksList.innerHTML = orderDetails.cart.map(item => `
            <div class="success-book-item">
                <img src="${item.image}" alt="${item.title}">
                <div>
                    <h4>${item.title}</h4>
                    <p><strong>Author:</strong> ${item.author}</p>
                    <p><strong>Price:</strong> ₹${(item.price * item.quantity).toFixed(2)} (${item.quantity || 1}x)</p>
                </div>
            </div>
        `).join('');
    }

    const buyerDetailsContainer = document.getElementById('buyerDetails');
    if (buyerDetailsContainer && orderDetails.name) {
        buyerDetailsContainer.innerHTML = `
            <p><strong>Name:</strong> ${orderDetails.name}</p>
            <p><strong>Email:</strong> ${orderDetails.email}</p>
            <p><strong>Phone:</strong> ${orderDetails.phone}</p>
            <p><strong>Address:</strong> ${orderDetails.street}, ${orderDetails.area}, ${orderDetails.district}, ${orderDetails.state} - ${orderDetails.pincode}</p>
            <p><strong>Total Paid:</strong> ₹${orderDetails.total}</p>
        `;
    }

    localStorage.removeItem('cart');
}

function continueShopping() {
    window.location.href = 'index.html';
}

// ---------------------- INIT ----------------------
document.addEventListener("DOMContentLoaded", updateCartCount);
