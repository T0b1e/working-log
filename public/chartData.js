// Function to fetch user data and group it by title
function fetchChartData() {
    console.log("Starting fetchChartData...");

    fetch('utils/fetch_users.php')
        .then(response => response.json())
        .then(data => {
            console.log("Data fetched from fetch_users.php:", data);

            if (data.success) {
                const messages = data.data;

                // Get today's date in YYYY-MM-DD format
                const today = new Date().toISOString().split('T')[0];
                console.log("Today's date:", today);

                // Filter messages by today's date
                const todayMessages = messages.filter(msg => {
                    const messageDate = new Date(msg.created_at).toISOString().split('T')[0];
                    return messageDate === today;
                });

                console.log("Messages filtered by today's date:", todayMessages);

                // Count users who uploaded today
                const uniqueUsersToday = new Set(todayMessages.map(msg => msg.username)).size;
                document.getElementById('user-upload-count').textContent = uniqueUsersToday;

                // Group messages by title
                const titleCounts = todayMessages.reduce((acc, message) => {
                    const title = message.title || 'ไม่มีหัวข้อ';
                    acc[title] = (acc[title] || 0) + 1;
                    return acc;
                }, {});

                console.log("Messages grouped by title:", titleCounts);

                // Prepare chart data
                const titles = Object.keys(titleCounts);
                const counts = Object.values(titleCounts);

                // Create chart
                createTitleChart(titles, counts);
            } else {
                console.error("Error in data:", data.message);
                document.getElementById('user-upload-count').textContent = 'ไม่สามารถโหลดข้อมูล';
            }
        })
        .catch(error => console.error("Error fetching data:", error));
}

// Function to create the title chart using Chart.js
function createTitleChart(titles, counts) {
    console.log("Creating chart with titles:", titles, "and counts:", counts);

    const ctx = document.getElementById('titleChart').getContext('2d');
    const titleChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: titles,
            datasets: [{
                label: 'จำนวนข้อความต่อหัวข้อ',
                data: counts,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    console.log("Chart created successfully.");
}

// Call fetchChartData on page load
window.onload = function() {
    console.log("Page loaded. Fetching chart data...");
    fetchChartData();
};
