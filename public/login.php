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
        <p id="error-message" style="color: red; display: none;"></p>
        <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting the traditional way

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // AJAX request to send login data
            fetch('../src/controllers/AuthController.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email: email, password: password })
            })
            .then(response => response.json())
            .then(data => {
                const errorMessage = document.getElementById('error-message');
                if (data.success) {
                    window.location.href = '../../public/dashboard.php'; // Redirect on success
                } else {
                    errorMessage.innerText = data.message; // Show error message
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>
