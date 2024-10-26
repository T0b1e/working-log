<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not authenticated
if (!isset($_COOKIE['authToken']) || !isset($_COOKIE['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../src/models/User.php';

// Fetch user data from the database
$user_id = $_COOKIE['user_id'];
$user = new User();
$userData = $user->readById($user_id);

if (!$userData) {
    echo "ไม่พบผู้ใช้.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 การตั้งค่าบัญชีผู้ใช้</title>
    <link href="https://fonts.googleapis.com/css2?family=TH+Sarabun+New:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/settings.css">
    <link rel="stylesheet" href="./css/globals.css">
    <link rel="stylesheet" href="./css/navbars.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-title"><a href="dashboard.php">🔙 กลับสู่แดชบอร์ด</a></div>
    </nav>

    <div class="settings-container">
        <h1>⚙️ การตั้งค่าบัญชีผู้ใช้</h1>

        <!-- User Information Form -->
        <div class="user-profile">
            <h2>👤 แก้ไขข้อมูลผู้ใช้</h2>
            <form action="update_user.php" method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div>
                    <label for="username">👤 ชื่อผู้ใช้</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                </div>
                <div>
                    <label for="email">📧 อีเมล</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                </div>
                <div>
                    <label for="address">🏠 ที่อยู่</label>
                    <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>">
                </div>
                <div>
                    <label for="phone">📞 เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                </div>
                <div>
                    <label>🏢 แผนก</label>
                    <input type="text" value="<?php echo htmlspecialchars($userData['department'] ?? 'ยังไม่ได้ระบุ'); ?>" disabled>
                </div>
                <div>
                    <label>⚖️ บทบาท</label>
                    <input type="text" value="<?php echo htmlspecialchars($userData['role']); ?>" disabled>
                </div>
                <button type="submit">💾 บันทึกการเปลี่ยนแปลง</button>
            </form>
        </div>

        <!-- Password Change Form -->
        <div class="password-change">
            <h2>🔒 เปลี่ยนรหัสผ่าน</h2>
            <form action="update_user.php" method="POST">
                <input type="hidden" name="action" value="update_password">
                <div>
                    <label for="current_password">🔑 รหัสผ่านปัจจุบัน</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>
                <div>
                    <label for="new_password">🔑 รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>
                <button type="submit">🔄 เปลี่ยนรหัสผ่าน</button>
            </form>
        </div>
    </div>
</body>
</html>
