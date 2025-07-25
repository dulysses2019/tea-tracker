// This code runs as soon as the page is loaded and ready.
document.addEventListener('DOMContentLoaded', function() {
    const togglePasswordButton = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    // Safety check in case the elements don't exist
    if (togglePasswordButton && passwordInput) {
        togglePasswordButton.addEventListener('click', function() {
            // Check the current type of the input field
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Optional: Change the icon to a "slashed" eye when visible
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    }
});

// Your existing handleLogin function goes here...
function handleLogin() {
    // ...
}
function handleLogin() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const messageEl = document.getElementById('loginMessage');

    if (!username || !password) {
        messageEl.textContent = 'Username and password are required.';
        messageEl.className = 'error-message';
        messageEl.style.display = 'block';
        return;
    }

    const data = { username: username, password: password };

    fetch('api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            // On successful login, redirect the user to the main application page.
            window.location.href = 'index.php';
        } else {
            messageEl.textContent = result.message;
            messageEl.className = 'error-message';
            messageEl.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Login Error:', error);
        messageEl.textContent = 'A network error occurred.';
        messageEl.className = 'error-message';
        messageEl.style.display = 'block';
    });
}