
<?php
// C:\xampp\htdocs\TechStore\admin\products.php

require_once __DIR__ . "/auth.php";

if (empty($_SESSION["admin_csrf_token"])) {
    $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["admin_csrf_token"];

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | TechStore</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #172033;
        }

        .layout { display: flex; min-height: 100vh; }

        aside {
            width: 230px;
            flex-shrink: 0;
            padding: 26px 16px;
            background: #0f172a;
            color: white;
        }

        .logo {
            margin: 0 0 32px;
            font-size: 24px;
            font-weight: 800;
        }

        .logo span { color: #60a5fa; }

        aside a {
            display: block;
            margin: 8px 0;
            padding: 12px;
            border-radius: 8px;
            color: #cbd5e1;
            text-decoration: none;
        }

        aside a.active, aside a:hover {
            background: #1e293b;
            color: white;
        }

        main {
            flex: 1;
            min-width: 0;
            padding: 30px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }

        h1 { margin: 0 0 6px; }
        .muted { color: #64748b; }

        .panel {
            background: white;
            padding: 22px;
            margin-bottom: 24px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, .04);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .field.full { grid-column: 1 / -1; }

        label {
            font-size: 14px;
            font-weight: 700;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
            background: white;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .help {
            font-size: 12px;
            color: #64748b;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        button, .button-link {
            padding: 10px 14px;
            border: 0;
            border-radius: 7px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .primary { background: #2563eb; color: white; }
        .secondary { background: #e2e8f0; color: #172033; }
        .edit { background: #dbeafe; color: #1d4ed8; }
        .delete { background: #fee2e2; color: #b91c1c; }

        button:disabled { opacity: .55; cursor: not-allowed; }

        .message {
            display: none;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
            overflow-wrap: anywhere;
        }

        .message.show { display: block; }
        .message.error { background: #fee2e2; color: #991b1b; }
        .message.success { background: #dcfce7; color: #166534; }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: top;
        }

        th { color: #64748b; font-size: 13px; }
        td { overflow-wrap: anywhere; }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: white;
        }

        .product-name { font-weight: 700; }
        .product-description {
            max-width: 260px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        .row-actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .row-actions button {
            padding: 8px 10px;
            font-size: 13px;
        }

        @media (max-width: 850px) {
            .layout { display: block; }
            aside { width: 100%; }
            aside a { display: inline-block; }
            main { padding: 18px; }
            .form-grid { grid-template-columns: 1fr; }
            .field.full { grid-column: auto; }
        }
    </style>
</head>

<body>
<div class="layout">
    <aside>
        <div class="logo">Tech<span>Store</span></div>
        <a href="index.php">Dashboard</a>
        <a href="orders.php">Orders</a>
        <a class="active" href="products.php">Products</a>
        <a href="../index.html">View Store</a>
        <a href="logout.php">Log Out</a>
    </aside>

    <main>
        <header class="topbar">
            <div>
                <h1>Manage Products</h1>
                <div class="muted">Add, edit, and remove store products.</div>
            </div>
            <a class="button-link secondary" href="index.php">Back to Dashboard</a>
        </header>

        <div id="message" class="message" role="status" aria-live="polite"></div>

        <section class="panel" id="product-form-panel">
            <h2 id="form-title">Add a Product</h2>

            <form id="product-form">
                <input type="hidden" id="product-id">

                <div class="form-grid">
                    <div class="field">
                        <label for="name">Product Name *</label>
                        <input id="name" maxlength="255" required>
                    </div>

                    <div class="field">
                        <label for="brand">Brand *</label>
                        <input id="brand" maxlength="100" required>
                    </div>

                    <div class="field">
                        <label for="category">Category *</label>
                        <input id="category" maxlength="100" placeholder="Smartphone, Laptop, Audio..." required>
                    </div>

                    <div class="field">
                        <label for="price">Price (₱) *</label>
                        <input id="price" type="number" min="0" step="0.01" required>
                    </div>

                    <div class="field full">
                        <label for="image">Main Image Path *</label>
                        <input id="image" maxlength="500" placeholder="images/product.png" required>
                        <div class="help">Use a path to an image inside your TechStore project.</div>
                    </div>

                    <div class="field full">
                        <label for="images">Additional Image Paths</label>
                        <input id="images" placeholder="images/product1.png, images/product2.png">
                        <div class="help">Separate image paths with commas.</div>
                    </div>

                    <div class="field full">
                        <label for="description">Product Description / Theme *</label>
                        <textarea id="description" maxlength="5000" required></textarea>
                    </div>

                    <div class="field">
                        <label for="stock">Stock Quantity *</label>
                        <input id="stock" type="number" min="0" step="1" required>
                    </div>

                    <div class="field">
                        <label for="rating">Rating (0–5)</label>
                        <input id="rating" type="number" min="0" max="5" step="0.1" value="0">
                    </div>

                    <div class="field">
                        <label for="reviews">Number of Reviews</label>
                        <input id="reviews" type="number" min="0" step="1" value="0">
                    </div>

                    <div class="field">
                        <label for="colors">Colors</label>
                        <input id="colors" placeholder="Black, Silver, White">
                    </div>

                    <div class="field full">
                        <label for="storage">Storage / Variants</label>
                        <input id="storage" placeholder="128GB, 256GB, 512GB">
                        <div class="help">Separate options with commas.</div>
                    </div>
                </div>

                <div class="actions">
                    <button class="primary" type="submit" id="save-button">Save Product</button>
                    <button class="secondary" type="button" id="cancel-button" hidden>Cancel Edit</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2>Saved Products</h2>
            <p class="muted">Products saved here are stored in your MySQL database.</p>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody id="products-table">
                    <tr><td colspan="6">Loading products...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
const apiUrl = "../api/admin-products.php";
const csrfToken = <?= json_encode($csrfToken) ?>;

const form = document.getElementById("product-form");
const tableBody = document.getElementById("products-table");
const messageBox = document.getElementById("message");
const saveButton = document.getElementById("save-button");
const cancelButton = document.getElementById("cancel-button");
const formTitle = document.getElementById("form-title");

let products = [];

function showMessage(text, type = "success") {
    messageBox.textContent = text;
    messageBox.className = "message show " + type;
}

function clearMessage() {
    messageBox.textContent = "";
    messageBox.className = "message";
}

function valueOf(id) {
    return document.getElementById(id).value.trim();
}

function commaList(value) {
    return value.split(",").map(item => item.trim()).filter(Boolean);
}

async function apiRequest(options = {}) {
    const response = await fetch(apiUrl, {
        method: options.method || "GET",
        credentials: "same-origin",
        cache: "no-store",
        headers: {
            ...(options.body ? { "Content-Type": "application/json" } : {}),
            ...(options.body ? { "X-CSRF-Token": csrfToken } : {})
        },
        body: options.body ? JSON.stringify(options.body) : undefined
    });

    const result = await response.json().catch(() => ({
        success: false,
        message: "The server returned an invalid response."
    }));

    if (response.status === 401) {
        window.location.href = "login.php";
        throw new Error(result.message || "Please log in again.");
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || "The request failed.");
    }

    return result;
}

function addCell(row, text) {
    const cell = document.createElement("td");
    cell.textContent = text == null ? "" : String(text);
    row.appendChild(cell);
    return cell;
}

function renderProducts() {
    tableBody.replaceChildren();

    if (products.length === 0) {
        const row = document.createElement("tr");
        const cell = addCell(row, "No products found. Add your first product above.");
        cell.colSpan = 6;
        tableBody.appendChild(row);
        return;
    }

    products.forEach(product => {
        const row = document.createElement("tr");

        const imageCell = document.createElement("td");
        const image = document.createElement("img");
        image.className = "product-image";
        image.src = "../" + String(product.image || "").replace(/^\/+/, "");
        image.alt = product.name || "Product image";
        image.onerror = () => {
            image.removeAttribute("src");
            image.alt = "Image unavailable";
        };
        imageCell.appendChild(image);
        row.appendChild(imageCell);

        const nameCell = document.createElement("td");
        const name = document.createElement("div");
        name.className = "product-name";
        name.textContent = product.name;
        nameCell.appendChild(name);

        const brand = document.createElement("div");
        brand.className = "muted";
        brand.textContent = (product.brand || "") + " · " + (product.category || "");
        nameCell.appendChild(brand);
        row.appendChild(nameCell);

        addCell(row, "₱" + Number(product.price).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));

        addCell(row, product.stock);

        const descriptionCell = document.createElement("td");
        const description = document.createElement("div");
        description.className = "product-description";
        description.textContent = product.description;
        descriptionCell.appendChild(description);
        row.appendChild(descriptionCell);

        const actionCell = document.createElement("td");
        const actions = document.createElement("div");
        actions.className = "row-actions";

        const editButton = document.createElement("button");
        editButton.type = "button";
        editButton.className = "edit";
        editButton.textContent = "Edit";
        editButton.addEventListener("click", () => startEdit(product.id));

        const deleteButton = document.createElement("button");
        deleteButton.type = "button";
        deleteButton.className = "delete";
        deleteButton.textContent = "Delete";
        deleteButton.addEventListener("click", () => deleteProduct(product.id));

        actions.append(editButton, deleteButton);
        actionCell.appendChild(actions);
        row.appendChild(actionCell);

        tableBody.appendChild(row);
    });
}

async function loadProducts() {
    tableBody.innerHTML = "<tr><td colspan='6'>Loading products...</td></tr>";

    try {
        const result = await apiRequest();
        products = result.products || [];
        renderProducts();
    } catch (error) {
        tableBody.replaceChildren();
        const row = document.createElement("tr");
        const cell = addCell(row, error.message);
        cell.colSpan = 6;
        tableBody.appendChild(row);
        showMessage(error.message, "error");
    }
}

function startEdit(id) {
    const product = products.find(item => Number(item.id) === Number(id));
    if (!product) return;

    clearMessage();

    document.getElementById("product-id").value = product.id;
    document.getElementById("name").value = product.name || "";
    document.getElementById("brand").value = product.brand || "";
    document.getElementById("category").value = product.category || "";
    document.getElementById("price").value = product.price;
    document.getElementById("image").value = product.image || "";
    document.getElementById("images").value = (product.images || []).join(", ");
    document.getElementById("description").value = product.description || "";
    document.getElementById("stock").value = product.stock;
    document.getElementById("rating").value = product.rating;
    document.getElementById("reviews").value = product.reviews;
    document.getElementById("colors").value = (product.colors || []).join(", ");
    document.getElementById("storage").value = (product.storage || []).join(", ");

    formTitle.textContent = "Edit Product";
    saveButton.textContent = "Save Changes";
    cancelButton.hidden = false;

    document.getElementById("product-form-panel").scrollIntoView({
        behavior: "smooth",
        block: "start"
    });
}

function resetForm() {
    form.reset();
    document.getElementById("product-id").value = "";
    document.getElementById("rating").value = "0";
    document.getElementById("reviews").value = "0";
    formTitle.textContent = "Add a Product";
    saveButton.textContent = "Save Product";
    cancelButton.hidden = true;
}

cancelButton.addEventListener("click", () => {
    resetForm();
    clearMessage();
});

form.addEventListener("submit", async event => {
    event.preventDefault();
    clearMessage();

    const id = valueOf("product-id");

    const productData = {
        action: id ? "update" : "add",
        ...(id ? { id: Number(id) } : {}),
        name: valueOf("name"),
        brand: valueOf("brand"),
        category: valueOf("category"),
        price: Number(valueOf("price")),
        image: valueOf("image"),
        images: commaList(valueOf("images")),
        description: valueOf("description"),
        stock: Number(valueOf("stock")),
        rating: Number(valueOf("rating") || 0),
        reviews: Number(valueOf("reviews") || 0),
        colors: commaList(valueOf("colors")),
        storage: commaList(valueOf("storage"))
    };

    saveButton.disabled = true;

    try {
        const result = await apiRequest({
            method: "POST",
            body: productData
        });

        showMessage(result.message);
        resetForm();
        await loadProducts();
    } catch (error) {
        showMessage(error.message, "error");
    } finally {
        saveButton.disabled = false;
    }
});

async function deleteProduct(id) {
    const product = products.find(item => Number(item.id) === Number(id));
    if (!product) return;

    if (!confirm("Delete \"" + product.name + "\"? This cannot be undone.")) {
        return;
    }

    clearMessage();

    try {
        const result = await apiRequest({
            method: "POST",
            body: { action: "delete", id: Number(id) }
        });

        showMessage(result.message);
        await loadProducts();
    } catch (error) {
        showMessage(error.message, "error");
    }
}

loadProducts();
</script>
</body>
</html>
