<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=TH+Sarabun+New:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/auth.css">
</head>
<body>
    <div class="auth-container">
        <h2>Login</h2>
        <form id="login-form">
            <input type="email" name="email" id="email" placeholder="Email" required>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p id="error-message" style="color: red; display: none;"></p> <!-- Error message section -->
        <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // Clear previous error messages
            const errorMessage = document.getElementById('error-message');
            errorMessage.style.display = 'none';

            // Send AJAX request to login
            fetch('../src/controllers/AuthController.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'dashboard.php'; // Redirect on success
                } else {
                    errorMessage.innerText = data.message || 'Invalid email or password.';
                    errorMessage.style.display = 'block'; // Show error in red
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessage.innerText = 'An error occurred. Please try again later.';
                errorMessage.style.display = 'block'; // Show error in red
            });
        });
    </script>
</body>
</html>
