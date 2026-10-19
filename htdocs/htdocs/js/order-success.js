// ================================
// ORDER SUCCESS PAGE
// ================================

// Get the last order
const order = JSON.parse(localStorage.getItem("lastOrder"));

// ================================
// CHECK IF ORDER EXISTS
// ================================

if (!order) {
  document.getElementById("success-name").textContent = "Customer";

  document.getElementById("success-order-id").textContent = "No order found";

  document.getElementById("success-payment").textContent = "-";

  document.getElementById("success-total").textContent = "₱0";
} else {
  // ================================
  // CUSTOMER INFORMATION
  // ================================

  document.getElementById("success-name").textContent = order.customer.name;

  document.getElementById("success-email").textContent = order.customer.email;

  document.getElementById("success-phone").textContent = order.customer.phone;

  // ================================
  // ORDER NUMBER
  // ================================

  document.getElementById("success-order-id").textContent = order.orderId;

  // ================================
  // DELIVERY INFORMATION
  // ================================

  document.getElementById("success-address").textContent =
    order.delivery.address;

  document.getElementById("success-city").textContent = order.delivery.city;

  document.getElementById("success-postal").textContent = order.delivery.postal;

  // ================================
  // PAYMENT METHOD
  // ================================

  let paymentText = order.payment;

  if (order.payment === "cod") {
    paymentText = "Cash on Delivery";
  }

  if (order.payment === "gcash") {
    paymentText = "GCash";
  }

  if (order.payment === "card") {
    paymentText = "Credit / Debit Card";
  }

  document.getElementById("success-payment").textContent = paymentText;

  // ================================
  // ORDER ITEMS
  // ================================

  const itemsContainer = document.getElementById("success-items");

  itemsContainer.innerHTML = "";

  order.items.forEach((item) => {
    const itemSubtotal = item.price * item.quantity;

    itemsContainer.innerHTML += `

      <div class="success-item">

        <img
          src="${item.image}"
          alt="${item.name}"
        >

        <div class="success-item-info">

          <h3>
            ${item.name}
          </h3>

          <p>
            Color:
            ${item.color || "Default"}
          </p>

          <p>
            Storage:
            ${item.storage || "Default"}
          </p>

          <p>
            Quantity:
            ${item.quantity}
          </p>

        </div>

        <strong>
          ₱${itemSubtotal.toLocaleString()}
        </strong>

      </div>

    `;
  });

  // ================================
  // TOTAL
  // ================================

  document.getElementById("success-total").textContent =
    "₱" + order.total.toLocaleString();
}
