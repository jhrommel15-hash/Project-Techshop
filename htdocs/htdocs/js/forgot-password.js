// ================================
// FORGOT PASSWORD - MYSQL
// ================================

const forgotForm = document.getElementById("forgot-password-form");

if (forgotForm) {
  forgotForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const email = document.getElementById("forgot-email").value.trim();

    const formData = new FormData();

    formData.append("email", email);

    try {
      const response = await fetch("api/forgot-password.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (!result.success) {
        alert(result.message);
        return;
      }

      alert("Account found for " + result.user.name + "!");
    } catch (error) {
      console.error("Forgot password error:", error);

      alert(
        "Unable to connect to the server. Make sure Apache and MySQL are running.",
      );
    }
  });
}
