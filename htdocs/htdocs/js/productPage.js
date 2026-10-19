/* ========================================
   TECHSTORE PRODUCT DETAILS
   Loads product information from MySQL
======================================== */

async function displayProductDetails() {
  if (!window.location.pathname.includes("product.html")) {
    return;
  }

  const params = new URLSearchParams(window.location.search);
  const productId = Number(params.get("id"));

  const product = products.find((item) => item.id === productId);

  const nameElement = document.getElementById("product-name");

  if (!product) {
    if (nameElement) {
      nameElement.textContent = "Product not found";
    }

    const description = document.getElementById("product-description");

    if (description) {
      description.textContent =
        "This product may have been removed or the product link is invalid.";
    }

    return;
  }

  let selectedColor = product.colors[0] || "";
  let selectedStorage = product.storage[0] || "";
  let quantity = 1;

  /* ========================================
     PRODUCT INFORMATION
  ======================================== */

  const mainImage = document.getElementById("main-image");

  if (mainImage) {
    mainImage.src = product.image;
    mainImage.alt = product.name;

    mainImage.onerror = function () {
      console.error("Product image failed to load:", this.src);
    };
  }

  document.getElementById("product-name").textContent = product.name;

  document.getElementById("product-rating").textContent =
    `⭐ ${product.rating} (${product.reviews} Reviews)`;

  document.getElementById("product-price").textContent =
    `₱${product.price.toLocaleString("en-PH")}`;

  document.getElementById("product-description").textContent =
    product.description;

  document.getElementById("product-brand").textContent = product.brand;

  document.getElementById("product-stock").textContent =
    product.stock > 0 ? `${product.stock} items available` : "Out of stock";

  /* ========================================
     IMAGE THUMBNAILS
  ======================================== */

  const thumbnailContainer = document.getElementById("thumbnail-container");

  thumbnailContainer.innerHTML = "";

  const productImages = product.images.length
    ? product.images
    : [product.image];

  productImages.forEach((imagePath) => {
    const thumbnail = document.createElement("img");

    thumbnail.src = imagePath;
    thumbnail.alt = `${product.name} thumbnail`;
    thumbnail.className = "thumb";
    thumbnail.loading = "lazy";

    thumbnail.addEventListener("click", () => {
      document.getElementById("main-image").src = imagePath;
    });

    thumbnailContainer.appendChild(thumbnail);
  });

  /* ========================================
     COLOR OPTIONS
  ======================================== */

  const colorContainer = document.getElementById("color-options");

  colorContainer.innerHTML = "";

  product.colors.forEach((color, index) => {
    const button = document.createElement("button");

    button.type = "button";
    button.className = "option-btn";
    button.textContent = color;

    if (index === 0) {
      button.classList.add("selected");
    }

    button.addEventListener("click", () => {
      colorContainer.querySelectorAll(".option-btn").forEach((item) => {
        item.classList.remove("selected");
      });

      button.classList.add("selected");
      selectedColor = color;
    });

    colorContainer.appendChild(button);
  });

  /* ========================================
     STORAGE OPTIONS
  ======================================== */

  const storageContainer = document.getElementById("storage-options");

  storageContainer.innerHTML = "";

  product.storage.forEach((storage, index) => {
    const button = document.createElement("button");

    button.type = "button";
    button.className = "option-btn";
    button.textContent = storage;

    if (index === 0) {
      button.classList.add("selected");
    }

    button.addEventListener("click", () => {
      storageContainer.querySelectorAll(".option-btn").forEach((item) => {
        item.classList.remove("selected");
      });

      button.classList.add("selected");
      selectedStorage = storage;
    });

    storageContainer.appendChild(button);
  });

  /* ========================================
     QUANTITY SELECTOR
  ======================================== */

  const quantityElement = document.getElementById("qty");
  const plusButton = document.getElementById("plus");
  const minusButton = document.getElementById("minus");

  quantityElement.textContent = quantity;

  plusButton.onclick = () => {
    if (quantity < product.stock) {
      quantity++;
      quantityElement.textContent = quantity;
    } else {
      alert(`Only ${product.stock} item(s) are available.`);
    }
  };

  minusButton.onclick = () => {
    if (quantity > 1) {
      quantity--;
      quantityElement.textContent = quantity;
    }
  };

  /* ========================================
     ADD TO CART
  ======================================== */

  document.getElementById("add-cart-btn").onclick = () => {
    if (product.stock < 1) {
      alert("Sorry, this product is out of stock.");
      return;
    }

    if (product.colors.length > 0 && !selectedColor) {
      alert("Please select a color.");
      return;
    }

    if (product.storage.length > 0 && !selectedStorage) {
      alert("Please select a storage option.");
      return;
    }

    let cart = JSON.parse(localStorage.getItem("cart")) || [];

    const existing = cart.find(
      (item) =>
        Number(item.id) === product.id &&
        item.color === (selectedColor || "Default") &&
        item.storage === (selectedStorage || "Default"),
    );

    const existingQuantity = existing ? Number(existing.quantity) : 0;

    if (existingQuantity + quantity > product.stock) {
      alert(
        `Sorry, only ${product.stock} ${product.name} item(s) are available.`,
      );
      return;
    }

    if (existing) {
      existing.quantity += quantity;
    } else {
      cart.push({
        cartId:
          product.id +
          "-" +
          (selectedColor || "Default") +
          "-" +
          (selectedStorage || "Default"),
        id: product.id,
        name: product.name,
        price: product.price,
        image: product.image,
        color: selectedColor || "Default",
        storage: selectedStorage || "Default",
        quantity: quantity,
      });
    }

    localStorage.setItem("cart", JSON.stringify(cart));

    if (typeof updateCartBadge === "function") {
      updateCartBadge();
    }

    alert(
      `${product.name}\n` +
        `Color: ${selectedColor || "Default"}\n` +
        `Storage: ${selectedStorage || "Default"}\n\n` +
        "Added to cart!",
    );
  };
}

/* ========================================
   WAIT FOR DATABASE PRODUCTS
======================================== */

if (typeof loadProducts === "function") {
  loadProducts().then(() => {
    displayProductDetails();
  });
}
