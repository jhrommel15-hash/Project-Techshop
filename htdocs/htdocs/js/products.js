
/* ========================================
   TECHSTORE PRODUCTS
   Load product data from the MySQL API
======================================== */

let products = [];
let productsLoaded = false;
let productsLoadPromise = null;

/* ========================================
   LOAD PRODUCTS FROM PHP API
======================================== */

async function loadProducts() {
  if (productsLoadPromise) {
    return productsLoadPromise;
  }

  productsLoadPromise = (async () => {
    try {
      const apiUrl = new URL(
        "/api/products.php",
        window.location.origin
      );

      const response = await fetch(apiUrl.href, {
        method: "GET",
        cache: "no-store",
        credentials: "same-origin"
      });

      const rawResponse = await response.text();

      console.log("Products API status:", response.status);
      console.log("Products API response:", rawResponse);

      if (!response.ok) {
        throw new Error(
          "Server returned " + response.status
        );
      }

      let result;

      try {
        result = JSON.parse(rawResponse);
      } catch (parseError) {
        console.error("Invalid JSON returned by API:", parseError);
        throw new Error(
          "The products API did not return valid JSON."
        );
      }

      if (
        !result ||
        result.success !== true ||
        !Array.isArray(result.products)
      ) {
        throw new Error(
          result?.message || "Invalid products data from API."
        );
      }

      products = result.products.map((product) => ({
        ...product,
        id: Number(product.id),
        price: Number(product.price),
        stock: Number(product.stock),
        rating: Number(product.rating),
        reviews: Number(product.reviews),
        images: Array.isArray(product.images)
          ? product.images
          : [],
        colors: Array.isArray(product.colors)
          ? product.colors
          : [],
        storage: Array.isArray(product.storage)
          ? product.storage
          : []
      }));

      productsLoaded = true;

      displayFeaturedProducts();

      if (typeof applyProductFilters === "function") {
        applyProductFilters();
      }

      if (typeof displayProductDetails === "function") {
        displayProductDetails();
      }

      if (typeof refreshWishlistProducts === "function") {
        refreshWishlistProducts();
      }

      return products;
    } catch (error) {
      console.error("Unable to load TechStore products:", error);

      const errorMessage = document.createElement("div");
      errorMessage.className = "no-products";

      const heading = document.createElement("h2");
      heading.textContent = "Unable to load products";

      const message = document.createElement("p");
      message.textContent =
        "Please refresh the page. If the problem continues, check the browser console.";

      errorMessage.append(heading, message);

      const featuredContainer =
        document.getElementById("product-list");

      const allProductsContainer =
        document.getElementById("all-products");

      if (featuredContainer) {
        featuredContainer.replaceChildren(
          errorMessage.cloneNode(true)
        );
      }

      if (allProductsContainer) {
        allProductsContainer.replaceChildren(
          errorMessage.cloneNode(true)
        );
      }

      productsLoadPromise = null;
      return [];
    }
  })();

  return productsLoadPromise;
}

/* ========================================
   CREATE A SAFE IMAGE URL
======================================== */

function getProductImageUrl(imagePath) {
  if (
    typeof imagePath !== "string" ||
    imagePath.trim() === ""
  ) {
    return "";
  }

  // Convert images/example.png into
  // https://your-domain.com/images/example.png
  const cleanPath = imagePath.trim().replace(/^\/+/, "");

  return new URL(
    cleanPath,
    window.location.origin + "/"
  ).href;
}

/* ========================================
   DISPLAY FEATURED PRODUCTS
======================================== */

function displayFeaturedProducts() {
  const container = document.getElementById("product-list");

  if (!container) {
    return;
  }

  container.replaceChildren();

  if (products.length === 0) {
    const message = document.createElement("div");
    message.className = "no-products";

    const heading = document.createElement("h2");
    heading.textContent = "No products available";

    message.appendChild(heading);
    container.appendChild(message);
    return;
  }

  products.forEach((product) => {
    const card = document.createElement("div");
    card.className = "product-card";

    card.addEventListener("click", () => {
      openProduct(product.id);
    });

    /* IMAGE AREA */

    const imageWrapper = document.createElement("div");
    imageWrapper.className = "product-image";

    const image = document.createElement("img");
    image.alt = product.name || "Product image";
    image.loading = "lazy";
    image.decoding = "async";

    const imageUrl = getProductImageUrl(product.image);

    if (imageUrl) {
      image.src = imageUrl;
    } else {
      console.error(
        "Product has no valid image path:",
        product.name,
        product.image
      );
    }

    image.addEventListener("error", () => {
      console.error(
        "Failed to load product image:",
        product.name,
        image.src
      );
    });

    /* WISHLIST BUTTON */

    const wishlistButton = document.createElement("button");
    wishlistButton.className = "wishlist-btn";
    wishlistButton.type = "button";
    wishlistButton.setAttribute(
      "aria-label",
      "Add " + product.name + " to wishlist"
    );

    const isWishlisted =
      typeof wishlist !== "undefined" &&
      Array.isArray(wishlist) &&
      wishlist.some(
        (item) => Number(item.id) === product.id
      );

    wishlistButton.innerHTML = isWishlisted
      ? '<i class="fa-solid fa-heart"></i>'
      : '<i class="fa-regular fa-heart"></i>';

    wishlistButton.addEventListener("click", (event) => {
      event.stopPropagation();

      if (typeof addToWishlist === "function") {
        addToWishlist(product.id, wishlistButton);
      } else {
        console.warn(
          "addToWishlist() was not found. Check js/wishlist.js."
        );
      }
    });

    imageWrapper.append(image, wishlistButton);

    /* PRODUCT INFORMATION */

    const info = document.createElement("div");
    info.className = "product-info";

    const name = document.createElement("h3");
    name.textContent = product.name || "Unnamed Product";

    const brand = document.createElement("p");
    brand.className = "brand";
    brand.textContent = product.brand || "";

    const rating = document.createElement("div");
    rating.className = "rating";
    rating.textContent = "⭐ " + product.rating;

    const price = document.createElement("div");
    price.className = "price";
    price.textContent =
      "₱" + product.price.toLocaleString("en-PH");

    const viewButton = document.createElement("button");
    viewButton.className = "view-product-btn";
    viewButton.type = "button";
    viewButton.textContent = "View Product";

    viewButton.addEventListener("click", (event) => {
      event.stopPropagation();
      openProduct(product.id);
    });

    info.append(name, brand, rating, price, viewButton);
    card.append(imageWrapper, info);
    container.appendChild(card);
  });
}

/* ========================================
   OPEN PRODUCT DETAILS
======================================== */

function openProduct(id) {
  window.location.href =
    "/product.html?id=" + encodeURIComponent(id);
}

/* ========================================
   INITIALIZE
======================================== */

loadProducts();
