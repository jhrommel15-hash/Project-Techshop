
"use strict";

// ========================================
// TECHSTORE CHECKOUT
// ========================================

// Cart and shipping
const SHIPPING_FEE = 120;

function getCart() {
  try {
    const savedCart = JSON.parse(localStorage.getItem("cart"));
    return Array.isArray(savedCart) ? savedCart : [];
  } catch (error) {
    console.error("Unable to read cart:", error);
    return [];
  }
}

let cart = getCart();

// HTML elements
const checkoutItems = document.getElementById("checkout-items");
const checkoutSubtotal = document.getElementById("checkout-subtotal");
const checkoutShipping = document.getElementById("checkout-shipping");
const checkoutTotal = document.getElementById("checkout-total");
const placeOrderButton = document.getElementById("place-order");

const nameInput = document.getElementById("customer-name");
const emailInput = document.getElementById("customer-email");
const phoneInput = document.getElementById("customer-phone");
const phoneCountry = document.getElementById("phone-country");
const phoneError = document.getElementById("phone-error");
const addressInput = document.getElementById("address");
const cityInput = document.getElementById("city");
const postalInput = document.getElementById("postal");

// Prevent duplicate order submissions
let isSubmitting = false;

// ========================================
// HELPER FUNCTIONS
// ========================================

function formatPeso(amount) {
  return "₱" + Number(amount || 0).toLocaleString("en-PH");
}

function showPhoneError(message) {
  if (phoneError) {
    phoneError.textContent = message;
  } else if (message) {
    alert(message);
  }
}

function showRequiredError(message) {
  alert(message);
}

function getSubtotal() {
  return cart.reduce((sum, item) => {
    const price = Number(item.price);
    const quantity = Number(item.quantity);

    if (
      !Number.isFinite(price) ||
      !Number.isFinite(quantity) ||
      price < 0 ||
      quantity < 1
    ) {
      return sum;
    }

    return sum + price * quantity;
  }, 0);
}

// ========================================
// PHONE NUMBER: NUMBERS ONLY
// ========================================

function getSelectedPhoneSettings() {
  if (!phoneCountry) return null;

  const option = phoneCountry.options[phoneCountry.selectedIndex];

  if (!option) return null;

  return {
    country: phoneCountry.value,
    dialCode: option.dataset.dial,
    length: Number(option.dataset.length)
  };
}

function updatePhoneInput() {
  if (!phoneInput || !phoneCountry) return;

  const settings = getSelectedPhoneSettings();

  if (!settings || !settings.length) {
    phoneInput.removeAttribute("maxlength");
    return;
  }

  phoneInput.maxLength = settings.length;

  // Remove all non-numeric characters and enforce the country's limit.
  phoneInput.value = phoneInput.value
    .replace(/\D/g, "")
    .slice(0, settings.length);

  phoneInput.placeholder =
    settings.country === "PH" ? "9171234567" : "Enter digits only";

  showPhoneError("");
}

if (phoneCountry && phoneInput) {
  phoneCountry.addEventListener("change", updatePhoneInput);

  phoneInput.addEventListener("input", function () {
    const settings = getSelectedPhoneSettings();

    if (!settings || !settings.length) {
      this.value = this.value.replace(/\D/g, "").slice(0, 15);
      return;
    }

    this.value = this.value
      .replace(/\D/g, "")
      .slice(0, settings.length);

    showPhoneError("");
  });

  // Prevent non-numeric characters from being typed.
  phoneInput.addEventListener("keydown", function (event) {
    const allowedKeys = [
      "Backspace",
      "Delete",
      "Tab",
      "Escape",
      "Enter",
      "ArrowLeft",
      "ArrowRight",
      "Home",
      "End"
    ];

    if (
      allowedKeys.includes(event.key) ||
      event.ctrlKey ||
      event.metaKey
    ) {
      return;
    }

    if (!/^\d$/.test(event.key)) {
      event.preventDefault();
    }
  });

  updatePhoneInput();
}

// ========================================
// VALIDATE PHONE NUMBER
// ========================================

function validatePhoneNumber() {
  if (!phoneInput) {
    return {
      valid: false,
      message: "The phone number field is missing."
    };
  }

  const settings = getSelectedPhoneSettings();

  if (!settings || !settings.dialCode || !settings.length) {
    return {
      valid: false,
      message: "Please select a valid country."
    };
  }

  const phone = phoneInput.value.trim();

  if (!phone) {
    return {
      valid: false,
      message: "Please enter your phone number."
    };
  }

  if (!/^\d+$/.test(phone)) {
    return {
      valid: false,
      message: "Phone numbers must contain digits only."
    };
  }

  if (phone.length !== settings.length) {
    return {
      valid: false,
      message:
        "The selected country requires exactly " +
        settings.length +
        " digits."
    };
  }

  // Philippine national numbers are entered without the leading 0.
  if (settings.country === "PH" && !/^[1-9]\d{9}$/.test(phone)) {
    return {
      valid: false,
      message:
        "Enter a valid 10-digit Philippine number without the leading 0."
    };
  }

  return {
    valid: true,
    country: settings.country,
    dialCode: settings.dialCode,
    nationalNumber: phone,
    number: settings.dialCode + phone,
    formatted: settings.dialCode + " " + phone
  };
}

// ========================================
// DISPLAY CHECKOUT ITEMS
// ========================================

function displayCheckout() {
  if (
    !checkoutItems ||
    !checkoutSubtotal ||
    !checkoutShipping ||
    !checkoutTotal
  ) {
    console.error(
      "Checkout summary elements are missing. Check checkout.html."
    );
    return;
  }

  cart = getCart();
  checkoutItems.innerHTML = "";

  if (cart.length === 0) {
    checkoutItems.innerHTML = `
      <div class="empty-checkout">
        <i class="fa-solid fa-cart-shopping"></i>
        <p>Your cart is empty.</p>
        <a href="products.html">Continue Shopping</a>
      </div>
    `;

    checkoutSubtotal.textContent = formatPeso(0);
    checkoutShipping.textContent = formatPeso(0);
    checkoutTotal.textContent = formatPeso(0);

    if (placeOrderButton) {
      placeOrderButton.disabled = true;
    }

    return;
  }

  let subtotal = 0;

  cart.forEach(function (item) {
    const price = Number(item.price) || 0;
    const quantity = Number(item.quantity) || 0;
    const itemTotal = price * quantity;

    subtotal += itemTotal;

    const itemElement = document.createElement("div");
    itemElement.className = "checkout-item";

    const image = document.createElement("img");
    image.src = item.image || "";
    image.alt = item.name || "Product";
    image.onerror = function () {
      this.style.display = "none";
    };

    const info = document.createElement("div");
    info.className = "checkout-item-info";

    const name = document.createElement("h3");
    name.textContent = item.name || "Product";

    const priceElement = document.createElement("p");
    priceElement.textContent = formatPeso(price);

    const color = document.createElement("small");
    color.textContent = "Color: " + (item.color || "Default");

    const storage = document.createElement("small");
    storage.textContent = "Storage: " + (item.storage || "Default");

    const quantityElement = document.createElement("small");
    quantityElement.textContent = "Quantity: " + quantity;

    info.append(
      name,
      priceElement,
      color,
      storage,
      quantityElement
    );

    const totalElement = document.createElement("strong");
    totalElement.textContent = formatPeso(itemTotal);

    itemElement.append(image, info, totalElement);
    checkoutItems.appendChild(itemElement);
  });

  checkoutSubtotal.textContent = formatPeso(subtotal);
  checkoutShipping.textContent = formatPeso(SHIPPING_FEE);
  checkoutTotal.textContent = formatPeso(subtotal + SHIPPING_FEE);

  if (placeOrderButton && !isSubmitting) {
    placeOrderButton.disabled = false;
  }
}

// ========================================
// PLACE ORDER
// ========================================

if (placeOrderButton) {
  placeOrderButton.addEventListener("click", async function (event) {
    event.preventDefault();

    if (isSubmitting) return;

    cart = getCart();

    if (cart.length === 0) {
      showRequiredError("Your cart is empty.");
      displayCheckout();
      return;
    }

    // Read form fields
    const name = nameInput ? nameInput.value.trim() : "";
    const email = emailInput ? emailInput.value.trim() : "";
    const address = addressInput ? addressInput.value.trim() : "";
    const city = cityInput ? cityInput.value.trim() : "";
    const postal = postalInput ? postalInput.value.trim() : "";

    if (!name || !email || !address || !city || !postal) {
      showRequiredError(
        "Please complete all customer and delivery information."
      );
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showRequiredError("Please enter a valid email address.");
      if (emailInput) emailInput.focus();
      return;
    }

    // Validate phone
    const phoneResult = validatePhoneNumber();

    if (!phoneResult.valid) {
      showPhoneError(phoneResult.message);

      if (!phoneError) {
        showRequiredError(phoneResult.message);
      }

      if (phoneInput) phoneInput.focus();
      return;
    }

    showPhoneError("");

    // Validate payment selection
    const selectedPayment = document.querySelector(
      'input[name="payment"]:checked'
    );

    if (!selectedPayment) {
      showRequiredError("Please select a payment method.");
      return;
    }

    const paymentMethod = selectedPayment.value;
    const subtotal = getSubtotal();
    const total = subtotal + SHIPPING_FEE;

    if (subtotal <= 0) {
      showRequiredError("Your cart contains invalid items.");
      return;
    }

    // Data sent to api/create-order.php
    const order = {
      customer: {
        name: name,
        email: email,
        phone: phoneResult.number,
        country: phoneResult.country,
        phoneFormatted: phoneResult.formatted
      },
      delivery: {
        address: address,
        city: city,
        postal: postal
      },
      payment: paymentMethod,
      items: cart,
      subtotal: subtotal,
      shipping: SHIPPING_FEE,
      total: total
    };

    const originalButtonHTML = placeOrderButton.innerHTML;

    isSubmitting = true;
    placeOrderButton.disabled = true;
    placeOrderButton.textContent = "Processing...";

    try {
      const response = await fetch("api/create-order.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json"
        },
        body: JSON.stringify(order)
      });

      const responseText = await response.text();

      let result;

      try {
        result = JSON.parse(responseText);
      } catch (error) {
        console.error("Invalid response from create-order.php:", responseText);

        throw new Error(
          "The server did not return JSON. Check api/create-order.php and its server response."
        );
      }

      if (!response.ok) {
        console.error("Order request failed:", response.status, result);

        throw new Error(
          result.message ||
          "The server rejected the order request (HTTP " +
          response.status +
          ")."
        );
      }

      if (!result.success) {
        throw new Error(result.message || "Unable to place your order.");
      }

      const savedOrder = {
        orderId: result.orderId,
        orderNumber: result.orderNumber,
        customer: order.customer,
        delivery: order.delivery,
        payment: paymentMethod,
        items: cart,
        subtotal: result.subtotal ?? subtotal,
        shipping: result.shipping ?? SHIPPING_FEE,
        total: result.total ?? total,
        date: new Date().toISOString()
      };

      localStorage.setItem("lastOrder", JSON.stringify(savedOrder));

      let orders = [];

      try {
        const savedOrders = JSON.parse(localStorage.getItem("orders"));

        if (Array.isArray(savedOrders)) {
          orders = savedOrders;
        }
      } catch (error) {
        console.warn("Could not read local order history:", error);
      }

      orders.unshift(savedOrder);
      localStorage.setItem("orders", JSON.stringify(orders));

      localStorage.removeItem("cart");

      window.location.href = "order-success.html";
    } catch (error) {
      console.error("Checkout error:", error);

      alert(
        error.message ||
        "Unable to connect to the server. Make sure Apache and MySQL are running."
      );

      isSubmitting = false;
      placeOrderButton.disabled = false;
      placeOrderButton.innerHTML = originalButtonHTML;
    }
  });
}

// ========================================
// INITIALIZE CHECKOUT
// ========================================

displayCheckout();
