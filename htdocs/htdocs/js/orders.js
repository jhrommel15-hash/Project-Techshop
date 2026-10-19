// ================================
// MY ORDERS
// ================================

// Get the orders container
const ordersList = document.getElementById("orders-list");

// Get the logged-in user
const loggedInUser = JSON.parse(localStorage.getItem("loggedInUser"));

// ================================
// CHECK LOGIN
// ================================

if (!loggedInUser) {
  alert("Please login first.");

  window.location.href = "login.html";
}

// ================================
// GET ALL SAVED ORDERS
// ================================

const orders = JSON.parse(localStorage.getItem("orders")) || [];

// ================================
// DISPLAY ORDERS
// ================================

function displayOrders() {
  if (!ordersList) return;

  ordersList.innerHTML = "";

  // No orders found
  if (orders.length === 0) {
    ordersList.innerHTML = `
      <div class="no-orders">

        <i class="fa-solid fa-box-open"></i>

        <h2>No Orders Yet</h2>

        <p>
          You haven't placed any orders yet.
        </p>

        <a href="products.html">
          Start Shopping
        </a>

      </div>
    `;

    return;
  }

  // ================================
  // DISPLAY EVERY ORDER
  // ================================

  orders.forEach((order) => {
    const orderElement = document.createElement("div");

    orderElement.className = "order-card";

    orderElement.innerHTML = `
      <div class="order-card-header">

        <div>
          <strong>
            Order #${order.orderId}
          </strong>

          <p class="order-date">
            ${new Date(order.date).toLocaleString()}
          </p>
        </div>

        <span class="order-status">
          Placed
        </span>

      </div>


      <div class="order-products">

        ${order.items
          .map(
            (item) => `
              <div class="order-product">

                <img
                  src="${item.image}"
                  alt="${item.name}"
                >

                <div class="order-product-info">

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

                <strong class="order-product-price">
                  ₱${(item.price * item.quantity).toLocaleString()}
                </strong>

              </div>
            `,
          )
          .join("")}

      </div>


      <div class="order-card-footer">

        <span>
          Total
        </span>

        <strong>
          ₱${order.total.toLocaleString()}
        </strong>

      </div>
    `;

    ordersList.appendChild(orderElement);
  });
}

// ================================
// START
// ================================

displayOrders();
