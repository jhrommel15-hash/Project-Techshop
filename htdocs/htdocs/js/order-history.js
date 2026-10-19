// ========================================
// ORDER HISTORY
// ========================================

const orderHistoryContainer = document.getElementById("order-history");

// ========================================
// GET SAVED ORDERS
// ========================================

// Get all previous orders
let orders = JSON.parse(localStorage.getItem("orders")) || [];

// Also check if there is a recent order
const lastOrder = JSON.parse(localStorage.getItem("lastOrder"));

// If there is a last order and it isn't already saved
if (lastOrder) {
  const alreadySaved = orders.some(
    (order) => order.orderId === lastOrder.orderId,
  );

  if (!alreadySaved) {
    orders.unshift(lastOrder);

    localStorage.setItem("orders", JSON.stringify(orders));
  }
}

// ========================================
// DISPLAY ORDERS
// ========================================

function displayOrders() {
  if (!orderHistoryContainer) {
    return;
  }

  orderHistoryContainer.innerHTML = "";

  // No orders
  if (orders.length === 0) {
    orderHistoryContainer.innerHTML = `

            <div class="empty-orders">

                <i class="fa-solid fa-box-open"></i>

                <h2>
                    No Orders Yet
                </h2>

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

  // Display orders
  orders.forEach((order) => {
    const orderDate = new Date(order.date).toLocaleDateString();

    let productsHTML = "";

    order.items.forEach((item) => {
      productsHTML += `

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

                    <strong>
                        ₱${(item.price * item.quantity).toLocaleString()}
                    </strong>

                </div>

            `;
    });

    orderHistoryContainer.innerHTML += `

            <div class="order-card">

                <div class="order-card-header">

                    <div>

                        <div class="order-number">
                            ${order.orderId}
                        </div>

                        <div class="order-date">
                            ${orderDate}
                        </div>

                    </div>

                    <span class="order-status">
                        Processing
                    </span>

                </div>


                ${productsHTML}


                <div class="order-card-footer">

                    <span>
                        Payment:
                        <strong>
                            ${order.payment}
                        </strong>
                    </span>

                    <span class="order-total">
                        Total:
                        ₱${order.total.toLocaleString()}
                    </span>

                </div>

            </div>

        `;
  });
}


// START


displayOrders();
