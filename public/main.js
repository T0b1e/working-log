document.addEventListener('DOMContentLoaded', () => {
    // Initialize page on load
    window.onload = function() {
        checkAndUpdateStatuses(); // Check and update overdue statuses
    };

    // Function to check and update statuses
    function checkAndUpdateStatuses() {
        fetch('utils/update_status.php') // Endpoint that updates overdue statuses
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(data.message); // Log number of records updated
                } else {
                    console.error('Status update error:', data.message);
                }
                // After updating statuses, fetch user data
                fetchUserData(); 
                fetchOptions(); // Populate dropdowns
                document.getElementById('editModal').style.display = 'none';
                document.getElementById('detailModal').style.display = 'none';
            })
            .catch(error => console.error('Error checking and updating status:', error));
    }

    // Handle form submission for editing
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(editForm);

            fetch('utils/update_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('แก้ไขข้อมูลสำเร็จ!');
                    document.getElementById('editModal').style.display = 'none';
                    fetchUserData();  // Refresh table data
                } else {
                    alert('เกิดข้อผิดพลาดในการแก้ไข: ' + data.message);
                }
            })
            .catch(error => console.error('Error editing message:', error));
        });
    }

    // Clear search inputs and reset criteria
    document.getElementById('clearSearchButton').addEventListener('click', function() {
        document.getElementById('searchTerm').value = '';
        document.getElementById('searchCriteria').value = 'username';
        fetchUserData(); // Reload data without search filters
    });

    // Fetch and populate dropdown options dynamically
    function fetchOptions() {
        fetch('utils/manage_tag.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateDropdown('priority', data.priorities);
                    populateDropdown('status', data.statuses);
                    populateDropdown('title', data.titles);
                    populateDropdown('editPriority', data.priorities);
                    populateDropdown('editStatus', data.statuses);
                    populateDropdown('editTitle', data.titles);
                } else {
                    console.error('Failed to fetch options', data.error);
                }
            })
            .catch(error => console.error('Error fetching options:', error));
    }
    
    // Populate dropdown options
    function populateDropdown(dropdownId, options) {
        const dropdown = document.getElementById(dropdownId);
        
        if (!dropdown) {
            return;
        }

        dropdown.innerHTML = ''; // Clear existing options

        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.name;
            optionElement.textContent = option.name;
            dropdown.appendChild(optionElement);
        });
    }
    

    // Handle file upload form submission
    const uploadForm = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileToUpload');
    const errorMessage = document.getElementById('error-message');
    const clearUploadButton = document.getElementById('clearUploadButton');
    const uploadProgress = document.getElementById('uploadProgress');

    if (uploadForm) {
        uploadForm.addEventListener('submit', function(event) {
            event.preventDefault();
            errorMessage.innerText = '';  // Clear previous messages

            if (fileInput.files.length > 0) {
                const fileExtension = fileInput.files[0].name.split('.').pop().toLowerCase();
                const forbiddenExtensions = ['exe', 'zip', 'apk'];
                if (forbiddenExtensions.includes(fileExtension)) {
                    errorMessage.innerText = 'ไม่อนุญาตให้อัปโหลดไฟล์ประเภท .exe, .zip, หรือ .apk';
                    return;
                }
            }
            
            // Show progress bar
            uploadProgress.style.display = 'block';
            uploadProgress.value = 0;

            const formData = new FormData(uploadForm);
            if (fileInput.files.length > 0) {
                formData.append('fileToUpload', fileInput.files[0]);
            }

            // Create XMLHttpRequest to track upload progress
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'utils/upload_handler.php', true);

            // Update progress bar
            xhr.upload.onprogress = function(event) {
                if (event.lengthComputable) {
                    const percentComplete = (event.loaded / event.total) * 100;
                    uploadProgress.value = percentComplete;
                }
            };

            // Handle response
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        alert('อัปโหลดข้อมูลสำเร็จ');
                        uploadForm.reset();  // Clear the form
                        fetchUserData();  // Refresh table
                    } else {
                        errorMessage.innerText = 'การอัปโหลดล้มเหลว: ' + data.message;
                    }
                } else {
                    errorMessage.innerText = 'การอัปโหลดล้มเหลว!';
                }
                uploadProgress.style.display = 'none'; // Hide progress bar after upload
            };

            xhr.onerror = function() {
                errorMessage.innerText = 'เกิดข้อผิดพลาดในการอัปโหลด';
                uploadProgress.style.display = 'none'; // Hide progress bar
            };

            // Send the request
            xhr.send(formData);
        });
        
        clearUploadButton.addEventListener('click', function() {
            fileInput.value = '';  // Clear the file input
            errorMessage.innerText = '';  // Clear any error messages
        });
    }

    // Fetch user data with optional pagination and search parameters
    function fetchUserData(searchCriteria = '', searchTerm = '', page = 1, limit = 10) {
        const userTableBody = document.querySelector('#userTable tbody');
        const recordCountLabel = document.getElementById('recordCount');

        const selectedLimit = document.getElementById('recordCountSelect').value;
        limit = (selectedLimit === 'ทั้งหมด') ? 0 : parseInt(selectedLimit);

        const startingIndex = (page - 1) * limit;

        userTableBody.innerHTML = '';  // Clear existing rows
        const loadingMessage = document.createElement('tr');
        loadingMessage.innerHTML = `<td colspan="13" style="text-align:center;">กำลังโหลด...</td>`; // Updated colspan
        userTableBody.appendChild(loadingMessage);

        fetch(`utils/fetch_users.php?criteria=${searchCriteria}&term=${encodeURIComponent(searchTerm)}&page=${page}&limit=${limit}`)
            .then(response => response.json())
            .then(data => {
                userTableBody.innerHTML = ''; // Clear loading message
                if (data.success) {
                    populateTable(data.data, startingIndex);
                    recordCountLabel.textContent = `จำนวนบันทึกทั้งสิ้น: ${data.totalRecords}`;
                    updatePagination(data.totalPages, page);
                } else {
                    alert('ข้อผิดพลาด: ' + data.message);
                    recordCountLabel.textContent = 'จำนวนบันทึกทั้งสิ้น: 0';
                }
            })
            .catch(error => {
                userTableBody.innerHTML = '';
                const errorRow = document.createElement('tr');
                errorRow.innerHTML = `<td colspan="13" style="text-align:center; color: red;">เกิดข้อผิดพลาดในการโหลดข้อมูล</td>`; // Updated colspan
                userTableBody.appendChild(errorRow);
                recordCountLabel.textContent = 'จำนวนบันทึกทั้งสิ้น: 0';
            });
    }

    // Populate the user table with fetched data
    function populateTable(data, startingIndex = 0) {
        const userTableBody = document.querySelector('#userTable tbody');
        userTableBody.innerHTML = ''; // Clear the table

        data.forEach((user, index) => {
            userTableBody.appendChild(createUserRow(user, startingIndex + index + 1));
        });
    }

	function createUserRow(user, index) {
		const row = document.createElement('tr');
		const uploadDate = user.created_at ? new Date(user.created_at).toLocaleDateString() : 'ไม่พบเจอ';
		const uploadTime = user.created_at ? new Date(user.created_at).toLocaleTimeString() : 'ไม่พบเจอ';
		const startDate = user.start_date ? new Date(user.start_date).toLocaleDateString() : 'ไม่พบเจอ';
		const endDate = user.end_date ? new Date(user.end_date).toLocaleDateString() : 'ไม่พบเจอ';

		// Determine status class based on status text
		let statusClass = '';
		if (user.status === 'เลยกำหนด') {
			statusClass = 'status-overdue';
		} else if (user.status === 'กำลังดำเนินการ') {
			statusClass = 'status-in-progress';
		} else if (user.status === 'ดำเนินการแล้วเสร็จ') {
			statusClass = 'status-completed';
		}

		row.innerHTML = `
			<td>${index}</td>
			<td>${uploadTime}</td>
			<td>${startDate}</td>
			<td>${endDate}</td>
			<td>${user.username}</td>
			<td>${user.title || 'ไม่พบเจอ'}</td>
			<td>${user.description || 'ไม่พบเจอ'}</td>
			<td class="${statusClass}">${user.status || 'ไม่พบเจอ'}</td>
			<td>${user.file_name || ''}</td>
			<td class="action-column"><button class="detail-btn" data-id="${user.message_id}"><i class="fas fa-info-circle"></i></button></td> 
			<td class="action-column"><button class="edit-btn" data-id="${user.message_id}"><i class="fas fa-edit"></i></button></td>
			<td class="action-column"><button class="delete-btn" data-id="${user.message_id}"><i class="fas fa-trash-alt"></i></button></td>
		`;
		return row;
	}

    // Handle clicks for dynamic elements in the user table
    document.querySelector('#userTable').addEventListener('click', function (event) {
        const target = event.target.closest('button');
        if (!target || !target.dataset.id) return;
        const messageId = target.dataset.id;

        if (target.classList.contains('detail-btn')) {
            fetchMessageAndDisplayModal(messageId, 'detail');
        } else if (target.classList.contains('edit-btn')) {
            fetchMessageAndDisplayModal(messageId, 'edit');
        } else if (target.classList.contains('delete-btn')) {
            handleDelete(messageId);
        }
    });

    // Fetch message details and display in the appropriate modal
    function fetchMessageAndDisplayModal(messageId, mode) {
        fetch(`utils/fetch_message.php?id=${messageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (mode === 'detail') {
                        displayDetailsModal(data.message);
                    } else if (mode === 'edit') {
                        populateEditModal(data.message);
                        document.getElementById('editModal').style.display = 'block';
                    }
                } else {
                    alert('ข้อผิดพลาด: ไม่สามารถดึงข้อมูลได้');
                }
            })
            .catch(error => console.error('Error fetching message details:', error));
    }

    // Populate Edit Modal with fetched message data
    function populateEditModal(message) {
        // Set dropdown values based on message data
        const editTitle = document.getElementById('editTitle');
        const editPriority = document.getElementById('editPriority');
        const editStatus = document.getElementById('editStatus');

        editTitle.value = message.title || '';
        editPriority.value = message.priority || '';
        editStatus.value = message.status || '';

        // Populate other fields
        document.getElementById('editDescription').value = message.description || '';
        document.getElementById('editMessageId').value = message.message_id || '';

        // New date fields
        document.getElementById('editStartDate').value = message.start_date || '';
        document.getElementById('editEndDate').value = message.end_date || '';

        // Show current file information if available
        const currentFileSection = document.getElementById('currentFileSection');
        if (message.file_name) {
            document.getElementById('currentFileName').textContent = message.file_name;
            currentFileSection.style.display = 'block';
        } else {
            currentFileSection.style.display = 'none';
        }

        document.getElementById('editModal').style.display = 'block'; // Display modal
    }

	function displayDetailsModal(message) {
		const modalDetails = document.getElementById('modalDetails');
		const fileLink = message.file_name 
			? `<a href="../uploads/${message.file_name}" target="_blank">${message.file_name}</a>` 
			: 'ไม่มีไฟล์';
		const startDate = message.start_date ? new Date(message.start_date).toLocaleDateString() : 'ไม่พบเจอ';
		const endDate = message.end_date ? new Date(message.end_date).toLocaleDateString() : 'ไม่พบเจอ';

		// Calculate overdue days if status is 'เลยกำหนด'
		let overdueText = '';
		if (message.status === 'เลยกำหนด' && message.end_date) {
			const endDateObj = new Date(message.end_date);
			const today = new Date();

			if (endDateObj < today) {
				const daysOverdue = Math.ceil((today - endDateObj) / (1000 * 60 * 60 * 24));
            	overdueText = `<p style="color: red;"><strong>ล้าช้า:</strong> ${daysOverdue} วัน</p>`;
			}
		}

		modalDetails.innerHTML = `
			<p><strong>หัวข้อ:</strong> ${message.title}</p>
			<p><strong>รายละเอียด:</strong> ${message.description}</p>
			<p><strong>วันที่จัดทำ:</strong> ${startDate}</p>
			<p><strong>วันที่สิ้นสุด:</strong> ${endDate}</p>
			<p><strong>สถานะ:</strong> ${message.status}</p>
			<p><strong>ลำดับความสำคัญ:</strong> ${message.priority || 'ไม่พบเจอ'}</p>
			<p><strong>ความคิดเห็น:</strong> ${message.comments || 'ไม่มีความคิดเห็นเพิ่มเติม'}</p>
			<p><strong>ไฟล์แนบ:</strong> ${fileLink}</p>
			${overdueText} <!-- Display overdue information if applicable -->
		`;
		document.getElementById('detailModal').style.display = 'block';
	}

    // Handle delete action for a message
    function handleDelete(messageId) {
        if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้?')) {
            fetch(`utils/delete_message.php?id=${messageId}`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('ลบข้อมูลสำเร็จ');
                        fetchUserData();
                    } else {
                        alert('ลบข้อมูลล้มเหลว: ' + data.message);
                    }
                })
                .catch(error => console.error('Error deleting message:', error));
        }
    }

    // Update pagination buttons based on total pages and current page
    function updatePagination(totalPages, currentPage) {
        const paginationContainer = document.getElementById('pagination-container');
        paginationContainer.innerHTML = '';

        for (let page = 1; page <= totalPages; page++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = page;
            pageButton.classList.add('pagination-button');
            if (page === currentPage) pageButton.classList.add('active');
            pageButton.addEventListener('click', () => fetchUserData('', '', page));
            paginationContainer.appendChild(pageButton);
        }
    }

    // Trigger data fetch on search
    document.getElementById('searchButton').addEventListener('click', function() {
        const searchCriteria = document.getElementById('searchCriteria').value;
        const searchTerm = document.getElementById('searchTerm').value;
        fetchUserData(searchCriteria, searchTerm);
    });

    // Update data display based on selected record count
    document.getElementById('recordCountSelect').addEventListener('change', function() {
        fetchUserData();
    });

    // Close modals
    document.getElementById('closeDetailModal').addEventListener('click', function() {
        document.getElementById('detailModal').style.display = 'none';
    });
    document.getElementById('closeModal').addEventListener('click', function() {
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('editForm').reset();
    });
});
