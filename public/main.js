document.addEventListener('DOMContentLoaded', () => {
    // Handle form submission for editing
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(editForm);
            const messageId = document.getElementById('editMessageId').value;
            
            fetch('utils/update_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('แก้ไขข้อมูลสำเร็จ!'); 
                    document.getElementById('editModal').style.display = 'none'; // Close modal on success
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
                    populateDropdown('editPriority', data.priorities);
                    populateDropdown('editStatus', data.statuses);
                    populateDropdown('title', data.titles);
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
        dropdown.innerHTML = ''; // Clear existing options

        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.name;
            optionElement.textContent = option.name;
            dropdown.appendChild(optionElement);
        });
    }

    // Initialize on page load
    window.onload = function() {
        fetchUserData();  // Load initial data
        fetchOptions();   // Populate dropdowns
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('detailModal').style.display = 'none';
    };

    // Handle file upload form
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const fileInput = document.getElementById('fileToUpload');
            const errorMessage = document.getElementById('error-message');
            errorMessage.innerText = '';  // Clear previous messages

            // Validate file type
            if (fileInput.files.length > 0) {
                const fileExtension = fileInput.files[0].name.split('.').pop().toLowerCase();
                const forbiddenExtensions = ['exe', 'zip', 'apk'];
                if (forbiddenExtensions.includes(fileExtension)) {
                    errorMessage.innerText = 'ไม่อนุญาตให้อัปโหลดไฟล์ประเภท .exe, .zip, หรือ .apk';
                    return;
                }
            }

            const formData = new FormData(uploadForm);
            if (fileInput.files.length > 0) {
                formData.append('fileToUpload', fileInput.files[0]);
            }

            fetch('utils/upload_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('อัปโหลดข้อมูลสำเร็จ');
                    fetchUserData();  // Refresh table
                } else {
                    errorMessage.innerText = 'การอัปโหลดล้มเหลว: ' + data.message;
                }
            })
            .catch(error => {
                errorMessage.innerText = 'การอัปโหลดล้มเหลว!';
            });
        });
    }

    // Fetch user data with optional pagination and search parameters
	function fetchUserData(searchCriteria = '', searchTerm = '', page = 1, limit = 10) {
		const userTableBody = document.querySelector('#userTable tbody');
		const recordCountLabel = document.getElementById('recordCount');

		// Get the selected limit from the dropdown
		const selectedLimit = document.getElementById('recordCountSelect').value;
		limit = (selectedLimit === 'ทั้งหมด') ? 0 : parseInt(selectedLimit);

		// Calculate the starting index for the current page
		const startingIndex = (page - 1) * limit;

		// Debugging: Check calculated values
		console.log("Selected limit:", selectedLimit);
		console.log("Current page:", page);
		console.log("Starting index for this page:", startingIndex);

		userTableBody.innerHTML = '';  // Clear existing rows
		const loadingMessage = document.createElement('tr');
		loadingMessage.innerHTML = `<td colspan="11" style="text-align:center;">กำลังโหลด...</td>`;
		userTableBody.appendChild(loadingMessage);

		fetch(`utils/fetch_users.php?criteria=${searchCriteria}&term=${encodeURIComponent(searchTerm)}&page=${page}&limit=${limit}`)
			.then(response => response.json())
			.then(data => {
				userTableBody.innerHTML = '';  // Clear loading message
				if (data.success) {
					populateTable(data.data, startingIndex); // Pass startingIndex to populateTable
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
				errorRow.innerHTML = `<td colspan="11" style="text-align:center; color: red;">เกิดข้อผิดพลาดในการโหลดข้อมูล</td>`;
				userTableBody.appendChild(errorRow);
				recordCountLabel.textContent = 'จำนวนบันทึกทั้งสิ้น: 0';
			});
	}


    // Populate the user table with fetched data
	function populateTable(data, startingIndex = 0) {
		const userTableBody = document.querySelector('#userTable tbody');
		userTableBody.innerHTML = '';  // Clear the table

		data.forEach((user, index) => {
			// Adjust index by adding startingIndex to reset for each page
			userTableBody.appendChild(createUserRow(user, startingIndex + index + 1));
		});
	}

    // Create a table row for each user record
    function createUserRow(user, index) {
        const row = document.createElement('tr');
        const uploadDate = user.created_at ? new Date(user.created_at).toLocaleDateString() : 'ไม่พบเจอ';
        const uploadTime = user.created_at ? new Date(user.created_at).toLocaleTimeString() : 'ไม่พบเจอ';

        row.innerHTML = `
            <td>${index}</td> <!-- Display calculated index -->
            <td>${uploadDate}</td>
            <td>${uploadTime}</td>
            <td>${user.username}</td>
            <td>${user.title || 'ไม่พบเจอ'}</td>
            <td>${user.description || 'ไม่พบเจอ'}</td>
            <td>${user.status || 'ไม่พบเจอ'}</td>
            <td>${user.file_name || ''}</td>
            <td><button class="detail-btn" data-id="${user.message_id}"><i class="fas fa-info-circle"></i></button></td> 
            <td><button class="edit-btn" data-id="${user.message_id}"><i class="fas fa-edit"></i></button></td>
            <td><button class="delete-btn" data-id="${user.message_id}"><i class="fas fa-trash-alt"></i></button></td>
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

    // Display the message details in a modal
    function displayDetailsModal(message) {
        const modalDetails = document.getElementById('modalDetails');
        const fileLink = message.file_name ? `<a href="../uploads/${message.file_name}" target="_blank">${message.file_name}</a>` : 'ไม่มีไฟล์';

        modalDetails.innerHTML = `
            <p>หัวข้อ: ${message.title}</p>
            <p>รายละเอียด: ${message.description}</p>
            <p>สถานะ: ${message.status}</p>
            <p>ไฟล์: ${fileLink}</p>
        `;
        
        document.getElementById('detailModal').style.display = 'block';
    }

    // Populate the edit modal with message data
    function populateEditModal(message) {
        document.getElementById('editTitle').value = message.title;
        document.getElementById('editDescription').value = message.description;
        document.getElementById('editPriority').value = message.priority;
        document.getElementById('editStatus').value = message.status;
        document.getElementById('editMessageId').value = message.message_id;

        const currentFileSection = document.getElementById('currentFileSection');
        if (message.file_name) {
            document.getElementById('currentFileName').textContent = message.file_name;
            currentFileSection.style.display = 'block';
        } else {
            currentFileSection.style.display = 'none';
        }
    }

    // Handle delete action for a message
    function handleDelete(messageId) {
        if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลนี้?')) {
            fetch(`utils/delete_message.php?id=${messageId}`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('ลบข้อมูลสำเร็จ');
                        fetchUserData(); // Refresh table after deletion
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
        fetchUserData(); // Re-fetch with new limit
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
