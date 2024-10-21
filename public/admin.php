<?php
session_start();

// Check if user is authenticated
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

    <h1>👥 จัดการผู้ใช้และแท็ก</h1>
    
    <div id="user-management">
        <h2>📋 ผู้ใช้ทั้งหมด</h2>
        <table id="userTable">
            <thead>
                <tr>
                    <th class="user-code">รหัสผู้ใช้</th>
                    <th class="user-name">ชื่อผู้ใช้</th>
                    <th>อีเมล</th>
                    <th>บทบาท</th>
                    <th>แผนก</th>
                    <th class="user-address">ที่อยู่</th>
                    <th>โทรศัพท์</th>
                    <th class="user-action">ลบผู้ใช้</th>
                </tr>
            </thead>
            <tbody>
                <!-- User data will be populated here -->
            </tbody>
        </table>
    </div>

    <div id="tag-management">
        <h2>📝 จัดการ Priority และ Status</h2>
        <div>
            <h3>Priority</h3>
            <ul id="priority-list"></ul>
            <input type="text" id="new-priority" placeholder="Add new priority">
            <button onclick="addPriority()">Add Priority</button>
        </div>

        <div>
            <h3>Status</h3>
            <ul id="status-list"></ul>
            <input type="text" id="new-status" placeholder="Add new status">
            <button onclick="addStatus()">Add Status</button>
        </div>
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

        // Fetch tags (priority and status)
        function fetchTags() {
            fetch('utils/manage_tag.php')
                .then(response => response.json())
                .then(data => {
                    console.log(data)
                    if (data.success) {
                        // Populate priority list
                        const priorityList = document.getElementById('priority-list');
                        priorityList.innerHTML = '';
                        data.priorities.forEach(priority => {
                            const li = document.createElement('li');
                            li.innerHTML = `${priority.name} <button onclick="editPriority(${priority.id}, '${priority.name}')">Edit</button> <button onclick="deletePriority(${priority.id})">Delete</button>`;
                            priorityList.appendChild(li);
                        });

                        // Populate status list
                        const statusList = document.getElementById('status-list');
                        statusList.innerHTML = '';
                        data.statuses.forEach(status => {
                            const li = document.createElement('li');
                            li.innerHTML = `${status.name} <button onclick="editStatus(${status.id}, '${status.name}')">Edit</button> <button onclick="deleteStatus(${status.id})">Delete</button>`;
                            statusList.appendChild(li);
                        });
                    }
                });
        }

        // Add priority
        function addPriority() {
            const priorityName = document.getElementById('new-priority').value;
            fetch('utils/manage_tag.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=add_priority&name=${priorityName}`
            }).then(() => fetchTags());
        }

        // Add status
        function addStatus() {
            const statusName = document.getElementById('new-status').value;
            fetch('utils/manage_tag.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=add_status&name=${statusName}`
            }).then(() => fetchTags());
        }

        // Edit priority
        function editPriority(id, name) {
            const newName = prompt("Enter new priority name:", name);
            if (newName) {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `edit_action=edit_priority&id=${id}&name=${newName}`
                }).then(() => fetchTags());
            }
        }

        // Edit status
        function editStatus(id, name) {
            const newName = prompt("Enter new status name:", name);
            if (newName) {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `edit_action=edit_status&id=${id}&name=${newName}`
                }).then(() => fetchTags());
            }
        }

        // Delete priority
        function deletePriority(id) {
            if (confirm("Are you sure you want to delete this priority?")) {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `delete_action=delete_priority&id=${id}`
                }).then(() => fetchTags());
            }
        }

        // Delete status
        function deleteStatus(id) {
            if (confirm("Are you sure you want to delete this status?")) {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `delete_action=delete_status&id=${id}`
                }).then(() => fetchTags());
            }
        }

        // Fetch data on page load
        window.onload = function() {
            fetchUsers();
            fetchTags();
        };
    </script>
</body>
</html>
