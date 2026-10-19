// ================================
// REGISTER SYSTEM - MYSQL
// ================================

const registerForm = document.getElementById("register-form");

registerForm.addEventListener("submit", async function (event) {
  event.preventDefault();

  // Get form values
  const name = document.getElementById("register-name").value.trim();
  const email = document.getElementById("register-email").value.trim();
  const password = document.getElementById("register-password").value;
  const confirmPassword = document.getElementById("confirm-password").value;

  // Check passwords
  if (password !== confirmPassword) {
    alert("Passwords do not match.");
    return;
  }

  // Prepare data
  const formData = new FormData();

  formData.append("name", name);
  formData.append("email", email);
  formData.append("password", password);

  try {
    // Send data to PHP
    const response = await fetch("api/register.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    // Registration failed
    if (!result.success) {
      alert(result.message);
      return;
    }

    // Registration successful
    alert(result.message);

    // Go to login
    window.location.href = "login.html";
  } catch (error) {
    console.error("Registration error:", error);

    alert(
      "Unable to connect to the server. Make sure Apache and MySQL are running.",
    );
  }
});
