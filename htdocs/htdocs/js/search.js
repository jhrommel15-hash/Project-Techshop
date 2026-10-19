
/* ========================================
   TECHSTORE PRODUCTS PAGE
   Uses products loaded from MySQL
======================================== */

let activeCategory = "all";
let activeSort = "default";
let activeSearch = "";

/* ========================================
   DISPLAY PRODUCTS
======================================== */

function displayProducts(productList) {
  const container = document.getElementById("all-products");

  if (!container) {
    return;
  }

  container.innerHTML = "";

  if (productList.length === 0) {
    container.innerHTML = `
      <div class="no-products">
        <h2>No products found</h2>
        <p>Try another search or category.</p>
      </div>
    `;
    return;
  }

  productList.forEach((product) => {
    const card = document.createElement("div");
    card.className = "product-card";

    const imageWrapper = document.createElement("div");
    imageWrapper.className = "product-image";
    imageWrapper.addEventListener("click", () => openProduct(product.id));

    const image = document.createElement("img");
    image.src = product.image;
    image.alt = product.name;
    image.loading = "lazy";
    imageWrapper.appendChild(image);

    const info = document.createElement("div");
    info.className = "product-info";

    const name = document.createElement("h3");
    name.textContent = product.name;

    const brand = document.createElement("p");
    brand.className = "product-brand";
    brand.textContent = product.brand;

    const rating = document.createElement("div");
    rating.className = "rating";
    rating.textContent = `⭐ ${product.rating}`;

    const price = document.createElement("div");
    price.className = "price";
    price.textContent = `₱${product.price.toLocaleString("en-PH")}`;

    const viewButton = document.createElement("button");
    viewButton.className = "view-product-btn";
    viewButton.type = "button";
    viewButton.textContent = "View Product";
    viewButton.addEventListener("click", () => openProduct(product.id));

    info.append(name, brand, rating, price, viewButton);
    card.append(imageWrapper, info);
    container.appendChild(card);
  });
}

/* ========================================
   OPEN PRODUCT DETAILS
======================================== */

function openProduct(id) {
  window.location.href = `product.html?id=${encodeURIComponent(id)}`;
}

/* ========================================
   FILTER, SEARCH, AND SORT
======================================== */

function applyProductFilters() {
  const container = document.getElementById("all-products");

  // This script also loads on other pages.
  if (!container || !productsLoaded) {
    return;
  }

  let filteredProducts = [...products];

  // Search by product name, brand, or category.
  if (activeSearch) {
    const searchText = activeSearch.toLowerCase();

    filteredProducts = filteredProducts.filter((product) =>
      product.name.toLowerCase().includes(searchText) ||
      product.brand.toLowerCase().includes(searchText) ||
      product.category.toLowerCase().includes(searchText)
    );
  }

  // Filter by category.
  if (activeCategory !== "all") {
    filteredProducts = filteredProducts.filter(
      (product) => product.category === activeCategory
    );
  }

  // Sort the filtered results.
  if (activeSort === "low-high") {
    filteredProducts.sort((a, b) => a.price - b.price);
  } else if (activeSort === "high-low") {
    filteredProducts.sort((a, b) => b.price - a.price);
  } else if (activeSort === "rating") {
    filteredProducts.sort((a, b) => b.rating - a.rating);
  }

  displayProducts(filteredProducts);
}

/* ========================================
   SEARCH
======================================== */

const searchInputs = document.querySelectorAll("#search-products");
const searchButton = document.getElementById("search-button");

const urlParams = new URLSearchParams(window.location.search);
activeSearch = (urlParams.get("search") || "").trim();

searchInputs.forEach((input) => {
  input.value = activeSearch;

  input.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
      event.preventDefault();
      submitProductSearch(input.value);
    }
  });
});

function submitProductSearch(value) {
  const searchText = value.trim();

  window.location.href =
    "products.html?search=" + encodeURIComponent(searchText);
}

if (searchButton) {
  searchButton.addEventListener("click", () => {
    const input = document.querySelector("#search-products");
    submitProductSearch(input ? input.value : "");
  });
}

/* ========================================
   CATEGORY FILTER BUTTONS
======================================== */

const categoryButtons = document.querySelectorAll(".category-btn");

categoryButtons.forEach((button) => {
  button.addEventListener("click", () => {
    categoryButtons.forEach((btn) => btn.classList.remove("active"));

    button.classList.add("active");
    activeCategory = button.dataset.category || "all";

    applyProductFilters();
  });
});

/* ========================================
   SORT PRODUCTS
======================================== */

const sortSelect = document.getElementById("sort-products");

if (sortSelect) {
  sortSelect.addEventListener("change", () => {
    activeSort = sortSelect.value;
    applyProductFilters();
  });
}

/* ========================================
   WAIT FOR DATABASE PRODUCTS
======================================== */

if (typeof loadProducts === "function") {
  loadProducts().then(() => {
    applyProductFilters();
  });
}
