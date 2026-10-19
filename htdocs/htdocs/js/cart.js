// ================================
// SHOPPING CART
// ================================

// Get cart elements
const cartContainer = document.getElementById("cart-items");
const totalElement = document.getElementById("cart-total");

// Get cart from localStorage
let cart = JSON.parse(localStorage.getItem("cart")) || [];

// Shipping fee
const shipping = 120;

// ================================
// DISPLAY CART
// ================================

function displayCart() {
  // Clear cart container
  cartContainer.innerHTML = "";

  // Calculate subtotal
  let subtotal = 0;

  // ================================
  // EMPTY CART
  // ================================

  if (cart.length === 0) {
    cartContainer.innerHTML = `
      <div class="empty-cart">

        <i class="fa-solid fa-cart-shopping"></i>

        <h2>Your cart is empty.</h2>

        <p>Add some products to your cart.</p>

        <a href="products.html">
          Continue Shopping
        </a>

      </div>
    `;

    // Reset subtotal
    const subtotalElement = document.getElementById("cart-subtotal");

    if (subtotalElement) {
      subtotalElement.textContent = "₱0";
    }

    // Reset total
    totalElement.textContent = "₱0";

    return;
  }

  // ================================
  // DISPLAY CART ITEMS
  // ================================

  cart.forEach((item, index) => {
    // Calculate item subtotal
    const itemSubtotal = Number(item.price) * Number(item.quantity);

    subtotal += itemSubtotal;

    cartContainer.innerHTML += `

      <div class="cart-item">

        <img
          src="${item.image}"
          alt="${item.name}"
        >


        <div class="cart-info">

          <h2>
            ${item.name}
          </h2>


          <p class="cart-price">
            ₱${Number(item.price).toLocaleString()}
          </p>


          <p class="cart-variant">
            Color:
            <strong>
              ${item.color || "Default"}
            </strong>
          </p>


          <p class="cart-variant">
            Storage:
            <strong>
              ${item.storage || "Default"}
            </strong>
          </p>


          <!-- ================================
               QUANTITY
          ================================= -->

          <div class="cart-quantity">

            <button
              type="button"
              onclick="decreaseQuantity(${index})"
            >
              −
            </button>


            <span>
              ${item.quantity}
            </span>


            <button
              type="button"
              onclick="increaseQuantity(${index})"
            >
              +
            </button>

          </div>


          <!-- ================================
               ITEM SUBTOTAL
          ================================= -->

          <p class="item-subtotal">

            Subtotal:

            <strong>
              ₱${itemSubtotal.toLocaleString()}
            </strong>

          </p>

        </div>


        <!-- ================================
             REMOVE BUTTON
        ================================= -->

        <button
          type="button"
          class="remove-btn"
          onclick="removeItem(${index})"
        >

          <i class="fa-solid fa-trash"></i>

          Remove

        </button>

      </div>

    `;
  });

  // ================================
  // DISPLAY SUBTOTAL
  // ================================

  const subtotalElement = document.getElementById("cart-subtotal");

  if (subtotalElement) {
    subtotalElement.textContent = "₱" + subtotal.toLocaleString();
  }

  // ================================
  // DISPLAY TOTAL
  // ================================

  const total = subtotal + shipping;

  totalElement.textContent = "₱" + total.toLocaleString();
}

// ================================
// REMOVE ITEM
// ================================

function removeItem(index) {
  // Make sure the item exists
  if (index < 0 || index >= cart.length) {
    return;
  }

  // Remove exactly this item
  cart.splice(index, 1);

  // Save updated cart
  localStorage.setItem("cart", JSON.stringify(cart));

  // Refresh cart
  displayCart();

  // Update cart badge
  if (typeof updateCartBadge === "function") {
    updateCartBadge();
  }
}

// ================================
// INCREASE QUANTITY
// ================================

function increaseQuantity(index) {
  // Get the selected cart item
  const item = cart[index];

  if (!item) {
    return;
  }

  // Find original product
  const product = products.find((product) => product.id === item.id);

  // Check stock
  if (product && item.quantity >= product.stock) {
    alert(`Only ${product.stock} ${product.name} item(s) are available.`);

    return;
  }

  // Increase quantity
  item.quantity++;

  // Save cart
  localStorage.setItem("cart", JSON.stringify(cart));

  // Refresh cart
  displayCart();

  // Update cart badge
  if (typeof updateCartBadge === "function") {
    updateCartBadge();
  }
}

// ================================
// DECREASE QUANTITY
// ================================

function decreaseQuantity(index) {
  // Get selected cart item
  const item = cart[index];

  if (!item) {
    return;
  }

  // If quantity is greater than 1
  if (item.quantity > 1) {
    item.quantity--;

    // Save cart
    localStorage.setItem("cart", JSON.stringify(cart));

    // Refresh cart
    displayCart();

    return;
  }

  // If quantity is 1,
  // remove the item completely
  cart.splice(index, 1);

  // Save cart
  localStorage.setItem("cart", JSON.stringify(cart));

  // Refresh cart
  displayCart();

  // Update cart badge
  if (typeof updateCartBadge === "function") {
    updateCartBadge();
  }
}

// ================================
// INITIAL DISPLAY
// ================================

displayCart();

// ================================
// CHECKOUT BUTTON
// ================================

const checkoutButton = document.getElementById("checkout-btn");

if (checkoutButton) {
  checkoutButton.addEventListener("click", () => {
    // Prevent checkout if cart is empty
    if (cart.length === 0) {
      alert("Your cart is empty.");

      return;
    }

    // Go to checkout
    window.location.href = "checkout.html";
  });
}
