// ================================
// RESET PASSWORD - MYSQL
// ================================

const resetForm = document.getElementById("reset-password-form");

if (resetForm) {
  resetForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const email = document.getElementById("reset-email").value.trim();
    const newPassword = document.getElementById("new-password").value;
    const confirmPassword = document.getElementById(
      "confirm-new-password",
    ).value;

    // Check password length
    if (newPassword.length < 6) {
      alert("Password must be at least 6 characters.");
      return;
    }

    // Check passwords match
    if (newPassword !== confirmPassword) {
      alert("Passwords do not match.");
      return;
    }

    const formData = new FormData();

    formData.append("email", email);
    formData.append("new_password", newPassword);

    try {
      const response = await fetch("api/reset_password.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (!result.success) {
        alert(result.message);
        return;
      }

      alert(result.message);

      // Go back to login
      window.location.href = "login.html";
    } catch (error) {
      console.error("Reset password error:", error);

      alert("Unable to connect to the server. Please try again.");
    }
  });
}
