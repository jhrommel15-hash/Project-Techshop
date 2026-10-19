// ================================
// ACCOUNT PAGE
// ================================

const loggedInUser = JSON.parse(localStorage.getItem("loggedInUser"));

// ================================
// CHECK LOGIN
// ================================

if (!loggedInUser) {
  alert("Please login first.");

  window.location.href = "login.html";
} else {
  // Display name
  const accountName = document.getElementById("account-name");

  if (accountName) {
    accountName.textContent = loggedInUser.name.trim().split(" ")[0];
  }

  // Display full name
  const accountFullName = document.getElementById("account-full-name");

  if (accountFullName) {
    accountFullName.textContent = loggedInUser.name;
  }

  // Display email
  const accountEmail = document.getElementById("account-email");

  if (accountEmail) {
    accountEmail.textContent = loggedInUser.email;
  }
}
