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
        <form action="../src/controllers/AuthController.php?action=login" method="POST">
        <input type="email" name="email" id="email" placeholder="Email" required>
        <input type="password" name="password" id="password" placeholder="Password" required>
        <button type="submit">Login</button>
        </form>
        <p id="error-message" style="color: red; display: none;"></p> 
        <p>Don't have an account? <a href="signup.php">Sign up here</a></p>

    </div>
</body>
</html>
