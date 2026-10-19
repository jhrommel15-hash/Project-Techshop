
const API_BASE = window.location.pathname.startsWith("/TechStore/")
  ? "/TechStore/api/"
  : "/api/";

// ================================
// AUTHENTICATION UI
// ================================

function updateAuthUI() {
  let user = null;

  try {
    user = JSON.parse(localStorage.getItem("loggedInUser"));
  } catch {
    localStorage.removeItem("loggedInUser");
  }

  const loginLink = document.getElementById("login-link");
  const userMenu = document.getElementById("user-menu");
  const username = document.getElementById("username");

  if (user) {
    if (loginLink) loginLink.style.display = "none";
    if (userMenu) userMenu.style.display = "flex";

    if (username) {
      username.textContent =
        typeof user.name === "string"
          ? user.name.trim().split(" ")[0]
          : "Account";
    }
  } else {
    if (loginLink) loginLink.style.display = "inline-flex";
    if (userMenu) userMenu.style.display = "none";
  }
}

// ================================
// LOGOUT
// ================================

async function logout() {
  try {
    await fetch(API_BASE + "logout.php", {
      method: "POST",
      credentials: "same-origin"
    });
  } catch (error) {
    console.error("Server logout request failed:", error);
  }

  localStorage.removeItem("loggedInUser");

  alert("You have been logged out.");
  window.location.href = "/login.html";
}

// ================================
// LOAD AUTHENTICATION UI
// ================================

updateAuthUI();
