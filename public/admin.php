<?php
session_start();
require_once '../src/models/User.php'; // Include User model

// Check authentication
if (!isset($_COOKIE['authToken']) || !isset($_COOKIE['role'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_COOKIE['role'];
$user_id = $_COOKIE['user_id'];

// Fetch username from database using user_id
$user = new User();
$userData = $user->readById($user_id);
$username = $userData['username'] ?? 'ผู้ใช้งาน'; // Default to 'ผู้ใช้งาน' if username not found
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจัดการผู้ใช้และแท็ก</title>
    <link rel="stylesheet" href="./css/globals.css">
    <link rel="stylesheet" href="./css/navbars.css">
    <link rel="stylesheet" href="./css/admin.css">
</head>
<body>
  <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-title"><a href="dashboard.php">📋 ระบบบันทึกปฏิบัติงาน</a></div>

        <div class="navbar-center">
            ผู้ใช้: <?php echo htmlspecialchars($username); ?>
        </div>

        <ul>
            <?php if ($user_role === 'admin'): ?>
                <li><a href="admin.php">🔧 แผงควบคุมผู้ดูแล</a></li>
			    <li><a href="view.php">📊 รายงาน</a></li>
            <?php endif; ?>
            <li><a href="settings.php">⚙️ การตั้งค่าผู้ใช้</a></li>
            <li><a href="logout.php">🚪 ออกจากระบบ</a></li>
        </ul>
    </nav>

    <h1>👥 จัดการผู้ใช้</h1>
    
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
                    <th class="user-action">การกระทำ</th>
                </tr>
            </thead>
            <tbody>
                <!-- ข้อมูลผู้ใช้จะแสดงในที่นี้ -->
            </tbody>
        </table>
    </div>

<div id="tag-management">
    <h2>📝 ปรับแต่ง ลำดับความสำคัญ, สถานะ</h2>

    <!-- Title Management Section -->
    <div id="title-management">
        <h3 class="heading-title">หัวข้อ</h3>
        <div class="tag-column">
            <ul id="title-list-left-side"></ul>
        </div>
        <div class="tag-column">
            <ul id="title-list-right-side"></ul>
        </div>
    </div>

    <!-- Input Group for Adding New Title -->
    <div class="input-group">
        <input type="text" id="new-title" placeholder="เพิ่มหัวข้อใหม่" aria-label="เพิ่มหัวข้อใหม่">
        <button onclick="addTitle()" aria-label="เพิ่มหัวข้อใหม่">เพิ่มหัวข้อใหม่</button>
    </div>

    <!-- Priority Management Section -->
    <div class="priority-management">
        <h3 class="heading-priority">ลำดับความสำคัญ</h3>
        <ul id="priority-list"></ul>
        <div class="input-group">
            <input type="text" id="new-priority" placeholder="เพิ่มลำดับความสำคัญใหม่" aria-label="เพิ่มลำดับความสำคัญใหม่">
            <button onclick="addPriority()" aria-label="เพิ่มลำดับความสำคัญใหม่">เพิ่มลำดับความสำคัญใหม่</button>
        </div>
    </div>

    <!-- Status Management Section -->
    <div class="status-management">
        <h3 class="heading-status">สถานะ</h3>
        <ul id="status-list"></ul>
        <div class="input-group">
            <input type="text" id="new-status" placeholder="เพิ่มสถานะใหม่" aria-label="เพิ่มสถานะใหม่">
            <button onclick="addStatus()" aria-label="เพิ่มสถานะใหม่">เพิ่มสถานะใหม่</button>
        </div>
    </div>
</div>



    <script>
        // Current user ID and role from cookies
        const currentUserId = <?php echo json_encode($current_user_id); ?>;
        const currentUserRole = <?php echo json_encode($user_role); ?>;

        // Remove user by user_id
        function removeUser(user_id) {
            if (confirm("คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้คนนี้?")) {
                fetch('utils/remove_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `user_id=${user_id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("ผู้ใช้ถูกลบเรียบร้อยแล้ว");
                        fetchUsers(); // Refresh the user list after deletion
                    } else {
                        alert("เกิดข้อผิดพลาด: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                });
            }
        }

        // Fetch all users
        function fetchUsers() {
            fetch('utils/fetch_all_users.php') 
                .then(response => response.json())
                .then(data => {
                    const userTableBody = document.querySelector('#userTable tbody');
                    userTableBody.innerHTML = '';

                    if (data.success) {
                        data.data.forEach(user => {
                            const isCurrentUser = user.user_id == currentUserId; // Check if this is the current user

                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td data-label="รหัสผู้ใช้">${user.user_id}</td>
                                <td data-label="ชื่อผู้ใช้">${user.username}</td>
                                <td data-label="อีเมล">${user.email}</td>
                                <td data-label="บทบาท">
                                    <select ${isCurrentUser ? 'disabled' : ''} onchange="handleRoleChange(${user.user_id}, this.value)" class="role-select" aria-label="เลือกบทบาท">
                                        <option value="user" ${user.role === 'user' ? 'selected' : ''}>ผู้ใช้</option>
                                        <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>ผู้ดูแลระบบ</option>
                                    </select>
                                    <button id="save-button-${user.user_id}" class="btn-save" style="display:none;" onclick="saveRoleChange(${user.user_id})" aria-label="บันทึกบทบาท">บันทึก</button>
                                </td>
                                <td data-label="แผนก">${user.department}</td>
                                <td data-label="ที่อยู่">${user.address}</td>
                                <td data-label="โทรศัพท์">${user.phone}</td>
                                <td data-label="การกระทำ">
                                    <button ${isCurrentUser ? 'disabled' : ''} onclick="removeUser(${user.user_id})" class="btn-delete" aria-label="ลบผู้ใช้">
                                        ลบ
                                    </button>
                                </td>
                            `;
                            userTableBody.appendChild(row);
                        });
                    }
                });
        }

        // Handle role change, show save button
        function handleRoleChange(userId, newRole) {
            const saveButton = document.getElementById(`save-button-${userId}`);
            saveButton.style.display = 'inline-flex'; // Show the save button
        }

        // Save the new role when the save button is clicked
        function saveRoleChange(userId) {
            const selectElement = document.querySelector(`#userTable select[onchange="handleRoleChange(${userId}, this.value)"]`);
            const newRole = selectElement.value;

            fetch('utils/update_user_role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `user_id=${userId}&role=${newRole}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('บทบาทได้ถูกเปลี่ยนสำเร็จ');
                    fetchUsers(); // Refresh user list after update
                } else {
                    alert('Error เปลี่ยนแปลงบทบาท: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
            });
        }

        // Fetch priorities, statuses, and titles
        function fetchTags() {
            fetch('utils/manage_tag.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate Priority List
                        const priorityList = document.getElementById('priority-list');
                        priorityList.innerHTML = '';
                        data.priorities.forEach(priority => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <span>${priority.name}</span>
                                <div class="button-group">
                                    <button onclick="editPriority(${priority.id}, '${priority.name}')" class="btn-edit" aria-label="แก้ไขลำดับความสำคัญ">
                                        แก้ไข
                                    </button>
                                    <button onclick="deletePriority(${priority.id})" class="btn-delete" aria-label="ลบลำดับความสำคัญ">
                                        ลบ
                                    </button>
                                </div>
                            `;
                            priorityList.appendChild(li);
                        });

                        // Populate Status List
                        const statusList = document.getElementById('status-list');
                        statusList.innerHTML = '';
                        data.statuses.forEach(status => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <span>${status.name}</span>
                                <div class="button-group">
                                    <button onclick="editStatus(${status.id}, '${status.name}')" class="btn-edit" aria-label="แก้ไขสถานะ">
                                        แก้ไข
                                    </button>
                                    <button onclick="deleteStatus(${status.id})" class="btn-delete" aria-label="ลบสถานะ">
                                        ลบ
                                    </button>
                                </div>
                            `;
                            statusList.appendChild(li);
                        });

                        // Populate Title Lists (Left and Right)
                        const titleListLeft = document.getElementById('title-list-left-side');
                        const titleListRight = document.getElementById('title-list-right-side');
                        titleListLeft.innerHTML = '';
                        titleListRight.innerHTML = '';

                        data.titles.forEach((title, index) => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <span>${title.name}</span>
                                <div class="button-group">
                                    <button onclick="editTitle(${title.id}, '${title.name}')" class="btn-edit" aria-label="แก้ไขหัวข้อ">
                                        แก้ไข
                                    </button>
                                    <button onclick="deleteTitle(${title.id})" class="btn-delete" aria-label="ลบหัวข้อ">
                                        ลบ
                                    </button>
                                </div>
                            `;
                            // Alternate titles between left and right lists
                            if (index % 2 === 0) {
                                titleListLeft.appendChild(li);
                            } else {
                                titleListRight.appendChild(li);
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching tags:', error);
                    alert('เกิดข้อผิดพลาดในการดึงข้อมูลแท็ก');
                });
        }

        // Add Title
        function addTitle() {
            const titleName = document.getElementById('new-title').value.trim();
            if (titleName === "") {
                alert("กรุณากรอกชื่อหัวข้อใหม่");
                return;
            }
            fetch('utils/manage_tag.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=add_title&name=${encodeURIComponent(titleName)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchTags();
                    document.getElementById('new-title').value = '';
                } else {
                    alert("เกิดข้อผิดพลาด: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error adding title:', error);
                alert('เกิดข้อผิดพลาดในการเพิ่มหัวข้อ');
            });
        }

        // Add Priority
        function addPriority() {
            const priorityName = document.getElementById('new-priority').value.trim();
            if (priorityName === "") {
                alert("กรุณากรอกลำดับความสำคัญใหม่");
                return;
            }
            fetch('utils/manage_tag.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=add_priority&name=${encodeURIComponent(priorityName)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchTags();
                    document.getElementById('new-priority').value = '';
                } else {
                    alert("เกิดข้อผิดพลาด: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error adding priority:', error);
                alert('เกิดข้อผิดพลาดในการเพิ่มลำดับความสำคัญ');
            });
        }

        // Add Status
        function addStatus() {
            const statusName = document.getElementById('new-status').value.trim();
            if (statusName === "") {
                alert("กรุณากรอกสถานะใหม่");
                return;
            }
            fetch('utils/manage_tag.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=add_status&name=${encodeURIComponent(statusName)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchTags();
                    document.getElementById('new-status').value = '';
                } else {
                    alert("เกิดข้อผิดพลาด: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error adding status:', error);
                alert('เกิดข้อผิดพลาดในการเพิ่มสถานะ');
            });
        }

        // Edit Title
        function editTitle(id, name) {
            const newName = prompt("กรุณากรอกชื่อหัวข้อใหม่:", name);
            if (newName && newName.trim() !== "") {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `edit_action=edit_title&id=${id}&name=${encodeURIComponent(newName.trim())}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTags();
                    } else {
                        alert("เกิดข้อผิดพลาด: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error editing title:', error);
                    alert('เกิดข้อผิดพลาดในการแก้ไขหัวข้อ');
                });
            } else {
                alert("ชื่อหัวข้อใหม่ไม่ถูกต้อง");
            }
        }

        // Edit Priority
        function editPriority(id, name) {
            const newName = prompt("กรุณากรอกชื่อลำดับความสำคัญใหม่:", name);
            if (newName && newName.trim() !== "") {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `edit_action=edit_priority&id=${id}&name=${encodeURIComponent(newName.trim())}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTags();
                    } else {
                        alert("เกิดข้อผิดพลาด: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error editing priority:', error);
                    alert('เกิดข้อผิดพลาดในการแก้ไขลำดับความสำคัญ');
                });
            } else {
                alert("ชื่อลำดับความสำคัญใหม่ไม่ถูกต้อง");
            }
        }

        // Edit Status
        function editStatus(id, name) {
            const newName = prompt("กรุณากรอกชื่อสถานะใหม่:", name);
            if (newName && newName.trim() !== "") {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `edit_action=edit_status&id=${id}&name=${encodeURIComponent(newName.trim())}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTags();
                    } else {
                        alert("เกิดข้อผิดพลาด: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error editing status:', error);
                    alert('เกิดข้อผิดพลาดในการแก้ไขสถานะ');
                });
            } else {
                alert("ชื่อสถานะใหม่ไม่ถูกต้อง");
            }
        }

        // Delete Title
        function deleteTitle(id) {
            if (confirm("คุณแน่ใจหรือไม่ว่าต้องการลบหัวข้อนี้?")) {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `delete_action=delete_title&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTags();
                    } else {
                        alert("เกิดข้อผิดพลาด: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting title:', error);
                    alert('เกิดข้อผิดพลาดในการลบหัวข้อ');
                });
            }
        }

        // Delete Priority
        function deletePriority(id) {
            if (confirm("คุณแน่ใจหรือไม่ว่าต้องการลบลำดับความสำคัญนี้?")) {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `delete_action=delete_priority&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTags();
                    } else {
                        alert("เกิดข้อผิดพลาด: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting priority:', error);
                    alert('เกิดข้อผิดพลาดในการลบลำดับความสำคัญ');
                });
            }
        }

        // Delete Status
        function deleteStatus(id) {
            if (confirm("คุณแน่ใจหรือไม่ว่าต้องการลบสถานะนี้?")) {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `delete_action=delete_status&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTags();
                    } else {
                        alert("เกิดข้อผิดพลาด: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting status:', error);
                    alert('เกิดข้อผิดพลาดในการลบสถานะ');
                });
            }
        }

        // Fetch data when the page loads
        window.onload = function() {
            fetchUsers();
            fetchTags();
        };
    </script>
</body>
</html>
