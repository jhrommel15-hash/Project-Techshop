// ================================
// CONTACT FORM
// ================================

const contactForm = document.getElementById("contact-form");

if (contactForm) {
  contactForm.addEventListener("submit", function (event) {
    event.preventDefault();

    const name = document.getElementById("contact-name").value.trim();
    const email = document.getElementById("contact-email").value.trim();
    const subject = document.getElementById("contact-subject").value.trim();
    const message = document.getElementById("contact-message").value.trim();

    if (!name || !email || !subject || !message) {
      alert("Please complete all fields.");
      return;
    }

    alert("Thank you, " + name + "! Your message has been received.");

    contactForm.reset();
  });
}
