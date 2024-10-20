<?php
session_start();

// ตรวจสอบว่าผู้ใช้ได้รับการตรวจสอบสิทธิ์หรือไม่
if (!isset($_COOKIE['authToken']) || !isset($_COOKIE['role'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_COOKIE['role'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้</title>
    <link rel="stylesheet" href="./css/globals.css">
    <link rel="stylesheet" href="./css/navbars.css">
    <link rel="stylesheet" href="./css/admin.css">
</head>
<body>

    <nav class="navbar">
        <div class="navbar-title"><a href="dashboard.php">🔙 กลับสู่แดชบอร์ด</a></div>
    </nav>

    <h1>👥 จัดการผู้ใช้</h1>
    <div id="user-management">
        <h2>📋 ผู้ใช้ทั้งหมด</h2>
        <table id="userTable">
            <thead>
                <tr>
                    <th>รหัสผู้ใช้</th>
                    <th>ชื่อผู้ใช้</th>
                    <th>อีเมล</th>
                    <th>บทบาท</th>
                    <th>แผนก</th>
                    <th>ที่อยู่</th>
                    <th>โทรศัพท์</th>
                    <th>การกระทำ</th>
                </tr>
            </thead>
            <tbody>
                <!-- User data will be populated here -->
            </tbody>
        </table>
    </div>

    <script>
        // Fetch users for management
        function fetchUsers() {
            fetch('utils/fetch_all_users.php') 
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Response from server:", data);

                    const userTableBody = document.querySelector('#userTable tbody');
                    userTableBody.innerHTML = '';

                    if (data.success) {
                        data.data.forEach(user => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td data-label="รหัสผู้ใช้">${user.user_id}</td>
                                <td data-label="ชื่อผู้ใช้">${user.username}</td>
                                <td data-label="อีเมล">${user.email}</td>
                                <td data-label="บทบาท">
                                    <select onchange="updateRole(${user.user_id}, this.value)" class="role-select">
                                        <option value="" disabled selected>เลือกบทบาท</option> <!-- Default disabled option -->
                                        <option value="user" ${user.role === 'user' ? 'selected' : ''}>ผู้ใช้</option>
                                        <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>ผู้ดูแล</option>
                                    </select>
                                </td>
                                <td data-label="ตำแหน่ง">${user.department}</td>
                                <td data-label="ที่อยู่">${user.address}</td>
                                <td data-label="โทรศัพท์">${user.phone}</td>
                                <td data-label="การกระทำ">
                                    <button onclick="removeUser(${user.user_id})">ลบผู้ใช้</button>
                                </td>
                            `;
                            userTableBody.appendChild(row);
                        });
                    } else {
                        console.error("Error fetching users:", data.message);
                    }
                })
                .catch(error => {
                    console.error("Fetch error:", error);
                });
        }

        function updateRole(userId, newRole) {
            fetch('utils/update_user_role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=${userId}&role=${newRole}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                } else {
                    alert("Error updating role: " + data.message);
                }
            });
        }

        function removeUser(userId) {
            if (confirm("คุณแน่ใจว่าต้องการลบผู้ใช้คนนี้?")) {
                fetch('utils/remove_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        fetchUsers(); // Refresh the user list
                    } else {
                        alert("Error removing user: " + data.message);
                    }
                });
            }
        }

        // Fetch users on page load
        window.onload = fetchUsers;
    </script>
</body>
</html>
