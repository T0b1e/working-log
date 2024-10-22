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

    <nav class="navbar">
        <div class="navbar-title"><a href="dashboard.php">🔙 กลับไปยังแดชบอร์ด</a></div>
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
                    <th class="user-action">ลบผู้ใช้</th>
                </tr>
            </thead>
            <tbody>
                <!-- ข้อมูลผู้ใช้จะแสดงในที่นี้ -->
            </tbody>
        </table>
    </div>

    <div id="tag-management">
        <h2>📝 ปรับแต่ง ลำดับความสำคัญ, สถานะ และหัวข้อ</h2>

        <div>
            <h3>หัวข้อ</h3>
            <ul id="title-list"></ul>
            <input type="text" id="new-title" placeholder="เพิ่มหัวข้อใหม่">
            <button onclick="addTitle()">เพิ่มหัวข้อใหม่</button>
        </div>

        <div>
            <h3>ลำดับความสำคัญ</h3>
            <ul id="priority-list"></ul>
            <input type="text" id="new-priority" placeholder="เพิ่มลำดับความสำคัญใหม่">
            <button onclick="addPriority()">เพิ่มลำดับความสำคัญใหม่</button>
        </div>

        <div>
            <h3>สถานะ</h3>
            <ul id="status-list"></ul>
            <input type="text" id="new-status" placeholder="เพิ่มสถานะใหม่">
            <button onclick="addStatus()">เพิ่มสถานะใหม่</button>
        </div>
    </div>

    <script>
        // Fetch all users
        function fetchUsers() {
            fetch('utils/fetch_all_users.php') 
                .then(response => response.json())
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
                                        <option value="user" ${user.role === 'user' ? 'selected' : ''}>ผู้ใช้</option>
                                        <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>ผู้ดูแลระบบ</option>
                                    </select>
                                </td>
                                <td data-label="แผนก">${user.department}</td>
                                <td data-label="ที่อยู่">${user.address}</td>
                                <td data-label="โทรศัพท์">${user.phone}</td>
                                <td data-label="การกระทำ">
                                    <button onclick="removeUser(${user.user_id})">ลบผู้ใช้</button>
                                </td>
                            `;
                            userTableBody.appendChild(row);
                        });
                    }
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
                            li.innerHTML = `${priority.name} <div class="button-group"><button onclick="editPriority(${priority.id}, '${priority.name}')">แก้ไข</button> <button onclick="deletePriority(${priority.id})">ลบ</button></div>`;
                            priorityList.appendChild(li);
                        });

                        // Populate Status List
                        const statusList = document.getElementById('status-list');
                        statusList.innerHTML = '';
                        data.statuses.forEach(status => {
                            const li = document.createElement('li');
                            li.innerHTML = `${status.name} <div class="button-group"><button onclick="editStatus(${status.id}, '${status.name}')">แก้ไข</button> <button onclick="deleteStatus(${status.id})">ลบ</button></div>`;
                            statusList.appendChild(li);
                        });

                        // Populate Title List
                        const titleList = document.getElementById('title-list');
                        titleList.innerHTML = '';
                        data.titles.forEach(title => {
                            const li = document.createElement('li');
                            li.innerHTML = `${title.name} <div class="button-group"><button onclick="editTitle(${title.id}, '${title.name}')">แก้ไข</button> <button onclick="deleteTitle(${title.id})">ลบ</button></div>`;
                            titleList.appendChild(li);
                        });
                    }
                });
        }

        // Add Title
        function addTitle() {
            const titleName = document.getElementById('new-title').value;
            fetch('utils/manage_tag.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=add_title&name=${titleName}`
            }).then(() => fetchTags());
        }

        // Add Priority
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

        // Add Status
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

        // Edit Title
        function editTitle(id, name) {
            const newName = prompt("กรุณากรอกชื่อหัวข้อใหม่:", name);
            if (newName) {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `edit_action=edit_title&id=${id}&name=${newName}`
                }).then(() => fetchTags());
            }
        }

        // Edit Priority
        function editPriority(id, name) {
            const newName = prompt("กรุณากรอกชื่อ Priority ใหม่:", name);
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

        // Edit Status
        function editStatus(id, name) {
            const newName = prompt("กรุณากรอกชื่อ Status ใหม่:", name);
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

        // Delete Title
        function deleteTitle(id) {
            if (confirm("คุณแน่ใจหรือไม่ว่าต้องการลบหัวข้อนี้?")) {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `delete_action=delete_title&id=${id}`
                }).then(() => fetchTags());
            }
        }

        // Delete Priority
        function deletePriority(id) {
            if (confirm("คุณแน่ใจหรือไม่ว่าต้องการลบ Priority นี้?")) {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `delete_action=delete_priority&id=${id}`
                }).then(() => fetchTags());
            }
        }

        // Delete Status
        function deleteStatus(id) {
            if (confirm("คุณแน่ใจหรือไม่ว่าต้องการลบ Status นี้?")) {
                fetch('utils/manage_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `delete_action=delete_status&id=${id}`
                }).then(() => fetchTags());
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
