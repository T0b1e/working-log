<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=TH+Sarabun+New:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/auth.css">
</head>
<body>
    <div class="auth-container">
        <h2>Create Account</h2>
        <!-- Form that submits data to AuthController -->
        <form action="../src/controllers/AuthController.php?action=signup" method="POST">
            <input type="text" name="username" id="username" placeholder="Username" required>
            <input type="email" name="email" id="email" placeholder="Email" required>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <input type="text" name="department" id="department" placeholder="Department" required>
            <input type="tel" name="phone" id="phone" placeholder="Phone Number" required>
            <input type="text" name="address" id="address" placeholder="Address" required>
            <button type="submit">Sign Up</button>
        </form>
        <p id="error-message" style="color: red; display: none;"></p> <!-- Error message section -->
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
