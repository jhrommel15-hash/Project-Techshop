// ================================
// LOGIN SYSTEM - MYSQL
// ================================

const loginForm = document.getElementById("login-form");

if (loginForm) {
  loginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const email = document.getElementById("login-email").value.trim();
    const password = document.getElementById("login-password").value;

    const formData = new FormData();

    formData.append("email", email);
    formData.append("password", password);

    try {
      const response = await fetch("api/login.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (!result.success) {
        alert(result.message);
        return;
      }

      // Save logged-in user
      localStorage.setItem("loggedInUser", JSON.stringify(result.user));

      alert("Welcome back, " + result.user.name + "!");

      window.location.href = "index.html";
    } catch (error) {
      console.error("Login error:", error);

      alert(
        "Unable to connect to the server. Make sure Apache and MySQL are running.",
      );
    }
  });
}
