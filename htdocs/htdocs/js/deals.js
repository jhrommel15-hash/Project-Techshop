// ================================
// DEALS PAGE
// ================================

const dealsContainer = document.getElementById("deals-products");

// ================================
// DEAL PRODUCTS
// ================================

const dealProducts = [
  {
    id: 1,
    discount: 10,
  },

  {
    id: 2,
    discount: 15,
  },

  {
    id: 3,
    discount: 20,
  },

  {
    id: 4,
    discount: 10,
  },
];

// ================================
// DISPLAY DEALS
// ================================

function displayDeals() {
  if (!dealsContainer) return;

  dealsContainer.innerHTML = "";

  dealProducts.forEach((deal) => {
    const product = products.find((item) => item.id === deal.id);

    if (!product) return;

    const newPrice = product.price - (product.price * deal.discount) / 100;

    const card = document.createElement("div");

    card.className = "deal-card";

    card.innerHTML = `
      <span class="deal-badge">
        -${deal.discount}%
      </span>

      <div class="deal-image">
        <img
          src="${product.image}"
          alt="${product.name}"
        >
      </div>

      <div class="deal-info">

        <h2>${product.name}</h2>

        <p class="deal-brand">
          ${product.brand}
        </p>

        <div class="deal-price">

          <span class="deal-old-price">
            ₱${product.price.toLocaleString()}
          </span>

          <span class="deal-new-price">
            ₱${newPrice.toLocaleString()}
          </span>

        </div>

        <button
          class="deal-button"
          onclick="openProduct(${product.id})"
        >
          <i class="fa-solid fa-cart-shopping"></i>
          View Product
        </button>

      </div>
    `;

    dealsContainer.appendChild(card);
  });
}

// ================================
// START
// ================================

displayDeals();
