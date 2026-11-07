
document.addEventListener("DOMContentLoaded", function () {

    // Registration form validation
    const registerForm = document.getElementById("registerForm");

    if (registerForm) {
        registerForm.addEventListener("submit", function (event) {
            const name = document.getElementById("full_name").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value;
            const role = document.getElementById("role").value;

            if (name === "" || email === "" || password === "") {
                event.preventDefault();
                alert("Please fill in all required fields.");
                return;
            }

            if (password.length < 8) {
                event.preventDefault();
                alert("Password must contain at least 8 characters.");
                return;
            }

            if (!["student", "company", "supervisor"].includes(role)) {
                event.preventDefault();
                alert("Please select a valid account type.");
            }
        });
    }

    // Show or hide password
    
document.addEventListener("DOMContentLoaded", function () {
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");

    if (passwordInput && togglePassword) {
        togglePassword.addEventListener("click", function () {
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                togglePassword.textContent = "Hide";
            } else {
                passwordInput.type = "password";
                togglePassword.textContent = "Show";
            }
        });
    }
});


    // Confirm before logging out
    const logoutLinks = document.querySelectorAll(".logout-link");

    logoutLinks.forEach(function (link) {
        link.addEventListener("click", function (event) {
            const confirmed = confirm("Are you sure you want to log out?");

            if (!confirmed) {
                event.preventDefault();
            }
        });
    });

    // Display the current year wherever this element is used
    const yearElement = document.getElementById("currentYear");

    if (yearElement) {
        yearElement.textContent = new Date().getFullYear();
    }

});
