
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>My Orders | TechStore</title>

    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/auth.css" />
    <link rel="stylesheet" href="css/responsive.css" />

    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    />

    <style>
      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #f1f5f9;
        color: #172033;
      }

      /* Back header — matches Track My Order */
      .orders-back-header {
        background: #0f172a;
        color: white;
        padding: 18px 7%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
      }

      .orders-back-header .logo {
        font-size: 24px;
        font-weight: bold;
      }

      .orders-back-header .logo span {
        color: #60a5fa;
      }

      .orders-back-header a {
        color: white;
        text-decoration: none;
        font-weight: 600;
      }

      .orders-back-header a:hover {
        color: #60a5fa;
      }

      /* Orders page */
      .orders-container {
        width: min(900px, 92%);
        margin: 40px auto;
      }

      .orders-heading {
        margin-bottom: 25px;
      }

      .orders-heading h1 {
        margin-top: 0;
        margin-bottom: 10px;
      }

      .orders-heading p {
        color: #64748b;
        line-height: 1.6;
      }

      .order-card {
        background: white;
        border-radius: 12px;
        padding: 22px;
        margin: 16px 0;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
      }

      .order-top,
      .order-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
      }

      .order-number {
        font-weight: bold;
        font-size: 18px;
        overflow-wrap: anywhere;
      }

      .status {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 20px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 13px;
        font-weight: 600;
      }

      .order-total {
        font-size: 22px;
        font-weight: bold;
        margin: 18px 0;
      }

      .order-bottom {
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
      }

      .shipping {
        color: #64748b;
        font-size: 14px;
      }

      .track-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 15px;
        background: #2563eb;
        color: white;
        border-radius: 7px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.2s;
      }

      .track-button:hover {
        background: #1d4ed8;
      }

      .empty,
      .loading,
      .error-message {
        background: white;
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
      }

      .empty p {
        color: #64748b;
        margin-bottom: 20px;
      }

      .browse-button {
        display: inline-block;
        padding: 10px 15px;
        background: #2563eb;
        color: white;
        border-radius: 7px;
        text-decoration: none;
      }

      .browse-button:hover {
        background: #1d4ed8;
      }

      @media (max-width: 550px) {
        .orders-back-header {
          padding: 16px 5%;
        }

        .orders-back-header .logo {
          font-size: 21px;
        }

        .orders-back-header a {
          font-size: 14px;
        }

        .orders-container {
          margin: 25px auto;
        }

        .order-card {
          padding: 18px;
        }

        .order-bottom {
          align-items: flex-start;
          flex-direction: column;
        }

        .track-button {
          width: 100%;
        }
      }
    </style>
  </head>

  <body>
    <!-- Back to Store Header -->
    <header class="orders-back-header">
      <div class="logo">Tech<span>Store</span></div>
      <a href="index.html">← Back to Store</a>
    </header>

    <!-- My Orders -->
    <main class="orders-container">
      <div class="orders-heading">
        <h1>My Orders</h1>
        <p>View your previous purchases and their current status.</p>
      </div>

      <div id="orders-list" class="loading" role="status" aria-live="polite">
        <i class="fa-solid fa-spinner fa-spin"></i>
        Loading your orders...
      </div>
    </main>

    <!-- JavaScript -->
    <script src="js/auth.js"></script>

    <script>
      async function loadOrders() {
        const container = document.getElementById("orders-list");

        try {
          const response = await fetch("api/my-orders.php", {
            credentials: "same-origin",
            cache: "no-store"
          });

          const result = await response.json();

          if (!response.ok || !result.success) {
            container.className = "error-message";
            container.textContent =
              result.message || "Unable to load your orders.";
            return;
          }

          if (
            !Array.isArray(result.orders) ||
            result.orders.length === 0
          ) {
            container.className = "empty";
            container.replaceChildren();

            const heading = document.createElement("h2");
            heading.textContent = "No orders yet";

            const paragraph = document.createElement("p");
            paragraph.textContent =
              "Your purchases will appear here after you place an order.";

            const link = document.createElement("a");
            link.href = "products.html";
            link.className = "browse-button";
            link.textContent = "Browse Products";

            container.append(heading, paragraph, link);
            return;
          }

          container.className = "";
          container.replaceChildren();

          result.orders.forEach((order) => {
            const card = document.createElement("article");
            card.className = "order-card";

            const top = document.createElement("div");
            top.className = "order-top";

            const number = document.createElement("div");
            number.className = "order-number";
            number.textContent = "Order #" + order.order_number;

            const status = document.createElement("span");
            status.className = "status";
            status.textContent = order.status;

            top.append(number, status);

            const total = document.createElement("div");
            total.className = "order-total";
            total.textContent =
              "₱" +
              Number(order.total).toLocaleString("en-PH", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
              });

            const bottom = document.createElement("div");
            bottom.className = "order-bottom";

            const shipping = document.createElement("span");
            shipping.className = "shipping";
            shipping.textContent =
              "Shipping: ₱" +
              Number(order.shipping).toLocaleString("en-PH", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
              });

            const track = document.createElement("a");
            track.className = "track-button";
            track.href = "track-order.html";

            const icon = document.createElement("i");
            icon.className = "fa-solid fa-truck";

            track.append(icon, document.createTextNode(" Track Order"));
            bottom.append(shipping, track);

            card.append(top, total, bottom);
            container.appendChild(card);
          });
        } catch (error) {
          console.error("Unable to load orders:", error);
          container.className = "error-message";
          container.textContent =
            "Unable to connect. Please try again later.";
        }
      }

      loadOrders();
    </script>
  </body>
</html>
