// ========================================
// WISHLIST SYSTEM
// ========================================

// Get wishlist from localStorage
let wishlist = JSON.parse(localStorage.getItem("wishlist")) || [];

// ========================================
// SAVE WISHLIST
// ========================================

function saveWishlist() {
  localStorage.setItem("wishlist", JSON.stringify(wishlist));

  updateWishlistBadge();
}

// ========================================
// ADD / REMOVE WISHLIST
// ========================================

function addToWishlist(productId, button) {
  const product = products.find((product) => product.id === productId);

  if (!product) {
    return;
  }

  // Check if product already exists
  const exists = wishlist.some((item) => item.id === productId);

  // =========================
  // REMOVE FROM WISHLIST
  // =========================

  if (exists) {
    wishlist = wishlist.filter((item) => item.id !== productId);

    if (button) {
      button.innerHTML = '<i class="fa-regular fa-heart"></i>';
    }
  }

  // =========================
  // ADD TO WISHLIST
  // =========================
  else {
    wishlist.push({
      id: product.id,

      name: product.name,

      brand: product.brand,

      price: product.price,

      image: product.image,

      rating: product.rating,
    });

    if (button) {
      button.innerHTML = '<i class="fa-solid fa-heart"></i>';
    }
  }

  // Save
  saveWishlist();

  // Refresh wishlist page if we're on it
  displayWishlist();
}

// ========================================
// REMOVE FROM WISHLIST PAGE
// ========================================

function removeFromWishlist(productId) {
  wishlist = wishlist.filter((item) => item.id !== productId);

  saveWishlist();

  displayWishlist();
}

// ========================================
// DISPLAY WISHLIST
// ========================================

function displayWishlist() {
  const container = document.getElementById("wishlist-items");

  // If wishlist container doesn't exist
  if (!container) {
    return;
  }

  container.innerHTML = "";

  // =========================
  // EMPTY WISHLIST
  // =========================

  if (wishlist.length === 0) {
    container.innerHTML = `

            <div class="empty-wishlist">

                <i class="fa-regular fa-heart"></i>

                <h2>
                    Your wishlist is empty ❤️
                </h2>

                <p>
                    Add products you like to your wishlist.
                </p>

                <a href="products.html">
                    Browse Products
                </a>

            </div>

        `;

    return;
  }

  // =========================
  // DISPLAY PRODUCTS
  // =========================

  wishlist.forEach((product) => {
    container.innerHTML += `

            <div class="wishlist-item">

                <img
                    src="${product.image}"
                    alt="${product.name}"
                >

                <div class="wishlist-info">

                    <h2>
                        ${product.name}
                    </h2>

                    <p>
                        ${product.brand}
                    </p>

                    <p class="wishlist-price">
                        ₱${product.price.toLocaleString()}
                    </p>

                    <p>
                        ⭐ ${product.rating}
                    </p>


                    <div class="wishlist-buttons">

                        <button
                            onclick="addWishlistToCart(${product.id})"
                        >
                            <i class="fa-solid fa-cart-shopping"></i>
                            Add to Cart
                        </button>


                        <button
                            onclick="removeFromWishlist(${product.id})"
                        >
                            <i class="fa-solid fa-trash"></i>
                            Remove
                        </button>

                    </div>

                </div>

            </div>

        `;
  });
}

// ========================================
// WISHLIST → CART
// ========================================

function addWishlistToCart(productId) {
  const product = products.find((product) => product.id === productId);

  if (!product) {
    return;
  }

  let cart = JSON.parse(localStorage.getItem("cart")) || [];

  const existing = cart.find((item) => item.id === productId);

  if (existing) {
    existing.quantity++;
  } else {
    cart.push({
      id: product.id,

      name: product.name,

      price: product.price,

      image: product.image,

      color: product.colors?.[0] || "Default",

      storage: product.storage?.[0] || "Default",

      quantity: 1,
    });
  }

  localStorage.setItem("cart", JSON.stringify(cart));

  alert(product.name + " added to cart! 🛒");

  // Update cart badge if available
  if (typeof updateCartBadge === "function") {
    updateCartBadge();
  }
}

// ========================================
// WISHLIST BADGE
// ========================================

function updateWishlistBadge() {
  const badge = document.getElementById("wishlist-count");

  if (!badge) {
    return;
  }

  badge.textContent = wishlist.length;
}

// ========================================
// LOAD WISHLIST
// ========================================

displayWishlist();

updateWishlistBadge();
