<?php
// report.php

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
$username = htmlspecialchars($userData['username'] ?? 'ผู้ใช้งาน'); // Sanitize output

// Month names in Thai
$thaiMonths = [
    'January' => 'มกราคม',
    'February' => 'กุมภาพันธ์',
    'March' => 'มีนาคม',
    'April' => 'เมษายน',
    'May' => 'พฤษภาคม',
    'June' => 'มิถุนายน',
    'July' => 'กรกฎาคม',
    'August' => 'สิงหาคม',
    'September' => 'กันยายน',
    'October' => 'ตุลาคม',
    'November' => 'พฤศจิกายน',
    'December' => 'ธันวาคม',
];

// Calculate last three months including the current month
$currentDate = new DateTime();
$months = [];
for ($i = 0; $i < 3; $i++) {
    $month = (clone $currentDate)->modify("-$i month");
    $months[] = [
        'name' => $thaiMonths[$month->format('F')],
        'year' => $month->format('Y'),
        'month' => $month->format('n') // 1-12
    ];
}

// Prepare the display range (two months ago to current month)
$startMonthDisplay = $months[2]['name'] . ' ' . ($months[2]['year'] + 543); // Two months ago
$endMonthDisplay = $months[0]['name'] . ' ' . ($months[0]['year'] + 543); // Current month
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 ระบบบันทึกปฏิบัติงาน</title>
    <link rel="stylesheet" href="./css/globals.css">
    <link rel="stylesheet" href="./css/navbars.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="./css/report.css"> <!-- CSS for report -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Chart.js Data Labels Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-title"><a href="dashboard.php">📋 ระบบบันทึกปฏิบัติงาน</a></div>

        <div class="navbar-center">
            ผู้ใช้: <?php echo $username; ?>
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Display Uploads in Last 3 Months -->
        <div class="number-display">
            <div class="uploads-number">
                อัปโหลดใน 3 เดือนที่ผ่านมา (<?php echo $startMonthDisplay; ?> - <?php echo $endMonthDisplay; ?>): <span id="uploadsLastThreeMonths">0</span>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- First Row: Ring Charts -->
            <div class="row ring-charts">
                <div class="chart-container">
                    <canvas id="totalMessagesChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="completedMessagesChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="overdueMessagesChart"></canvas>
                </div>
            </div>

            <!-- Second Row: Bar Charts -->
            <div class="row bar-charts">
                <div class="chart-container">
                    <canvas id="topicsBarChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="topUsersChart"></canvas>
                </div>
            </div>

            <!-- Third Row: Single Bar Chart -->
            <div class="row single-bar-chart">
                <canvas id="uploadsBarChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Script to Fetch Data and Render Charts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Register the Data Labels plugin
            Chart.register(ChartDataLabels);

            // Fetch data from report_data.php
            fetch('utils/report_data.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Data received:', data);
                    if (data.success) {
                        const reportData = data.data;
                        console.log('Report Data:', reportData);
                        renderCharts(reportData);
                    } else {
                        console.error('Failed to fetch data:', data.message);
                        alert('ไม่สามารถดึงข้อมูลได้: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    alert('เกิดข้อผิดพลาดในการดึงข้อมูล');
                });

            function renderCharts(data) {
                // Destructure data
                const { totalMessages, completedMessages, overdueMessages, topicsData, topUsersData, uploadsPerMonth, uploadsLastThreeMonths } = data;

                // Update the number of uploads in the last 3 months
                document.getElementById('uploadsLastThreeMonths').textContent = uploadsLastThreeMonths || 0;

                // Total Messages Chart
                const totalMessagesCtx = document.getElementById('totalMessagesChart').getContext('2d');
                const totalMessagesChart = new Chart(totalMessagesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['จำนวนบันทึกทั้งหมด'],
                        datasets: [{
                            data: [totalMessages],
                            backgroundColor: ['#36A2EB']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            datalabels: {
                                formatter: (value, context) => {
                                    return value;
                                },
                                color: '#fff',
                                font: {
                                    weight: 'bold',
                                    size: 14
                                }
                            },
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'จำนวนบันทึกทั้งหมด',
                                font: {
                                    size: 16 // Increased font size for better visibility
                                }
                            }
                        }
                    }
                });

                // Completed Messages Chart
                const completedMessagesCtx = document.getElementById('completedMessagesChart').getContext('2d');
                const completedMessagesChart = new Chart(completedMessagesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['ดำเนินการแล้วเสร็จ', 'อื่นๆ'],
                        datasets: [{
                            data: [completedMessages, totalMessages - completedMessages],
                            backgroundColor: ['#4BC0C0', '#E7E9ED']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            datalabels: {
                                formatter: (value, context) => {
                                    return value;
                                },
                                color: '#fff',
                                font: {
                                    weight: 'bold',
                                    size: 14
                                }
                            },
                            legend: {
                                display: true,
                                labels: {
                                    font: {
                                        size: 12 // Reduced font size
                                    }
                                }
                            },
                            title: {
                                display: true,
                                text: 'บันทึกที่ดำเนินการแล้วเสร็จ',
                                font: {
                                    size: 16 // Increased font size for better visibility
                                }
                            }
                        }
                    }
                });

                // Overdue Messages Chart
                const overdueMessagesCtx = document.getElementById('overdueMessagesChart').getContext('2d');
                const overdueMessagesChart = new Chart(overdueMessagesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['เลยกำหนด', 'อื่นๆ'],
                        datasets: [{
                            data: [overdueMessages, totalMessages - overdueMessages],
                            backgroundColor: ['#FF6384', '#E7E9ED']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            datalabels: {
                                formatter: (value, context) => {
                                    return value;
                                },
                                color: '#fff',
                                font: {
                                    weight: 'bold',
                                    size: 14
                                }
                            },
                            legend: {
                                display: true,
                                labels: {
                                    font: {
                                        size: 12 // Reduced font size
                                    }
                                }
                            },
                            title: {
                                display: true,
                                text: 'บันทึกที่เลยกำหนด',
                                font: {
                                    size: 16 // Increased font size for better visibility
                                }
                            }
                        }
                    }
                });

                // Bar Chart for Topics
                const topicsCtx = document.getElementById('topicsBarChart').getContext('2d');
                const topicLabels = topicsData.map(item => item.title);
                const topicCounts = topicsData.map(item => item.message_count);

                const topicsBarChart = new Chart(topicsCtx, {
                    type: 'bar',
                    data: {
                        labels: topicLabels,
                        datasets: [{
                            label: 'จำนวนบันทึกต่อหัวข้อ',
                            data: topicCounts,
                            backgroundColor: '#FFCE56'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: (value, context) => {
                                    return value;
                                },
                                color: '#000',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                }
                            },
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'จำนวนบันทึกต่อหัวข้อ',
                                font: {
                                    size: 16 // Increased font size for better visibility
                                }
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'หัวข้อ',
                                    font: {
                                        size: 14 // Increased font size for better visibility
                                    }
                                },
                                ticks: {
                                    font: {
                                        size: 12 // Reduced font size
                                    }
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'จำนวนบันทึก',
                                    font: {
                                        size: 14 // Increased font size for better visibility
                                    }
                                },
                                ticks: {
                                    font: {
                                        size: 12 // Reduced font size
                                    },
                                    beginAtZero: true
                                }
                            }
                        }
                    }
                });

                // Top Users Chart
                const topUsersCtx = document.getElementById('topUsersChart').getContext('2d');
                const topUserLabels = topUsersData.map(item => item.username);
                const topUserCounts = topUsersData.map(item => item.message_count);

                const topUsersChart = new Chart(topUsersCtx, {
                    type: 'bar',
                    data: {
                        labels: topUserLabels,
                        datasets: [{
                            label: 'จำนวนบันทึกที่ส่ง',
                            data: topUserCounts,
                            backgroundColor: '#8E44AD'
                        }]
                    },
                    options: {
                        indexAxis: 'y', // Horizontal bar chart
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            datalabels: {
                                anchor: 'end',
                                align: 'right',
                                formatter: (value, context) => {
                                    return value;
                                },
                                color: '#000',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                }
                            },
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: '5 ผู้ใช้ที่ส่งบันทึกมากที่สุดใน 3 เดือนที่ผ่านมา',
                                font: {
                                    size: 16 // Increased font size for better visibility
                                }
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'จำนวนบันทึก',
                                    font: {
                                        size: 14 // Increased font size for better visibility
                                    }
                                },
                                ticks: {
                                    font: {
                                        size: 12 // Reduced font size
                                    },
                                    beginAtZero: true
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'ผู้ใช้',
                                    font: {
                                        size: 14 // Increased font size for better visibility
                                    }
                                },
                                ticks: {
                                    font: {
                                        size: 12 // Reduced font size
                                    }
                                }
                            }
                        }
                    }
                });

                // Bar Chart for Last 3 Months Uploads
                const uploadsCtx = document.getElementById('uploadsBarChart').getContext('2d');
                const uploadLabels = Object.keys(uploadsPerMonth).reverse(); // Ensure chronological order
                const uploadCounts = Object.values(uploadsPerMonth).reverse();

                const uploadsBarChart = new Chart(uploadsCtx, {
                    type: 'bar',
                    data: {
                        labels: uploadLabels,
                        datasets: [{
                            label: 'จำนวนการอัปโหลด',
                            data: uploadCounts,
                            backgroundColor: [
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)',
                                'rgba(255, 159, 64, 0.7)'
                            ],
                            borderColor: [
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: (value, context) => {
                                    return value;
                                },
                                color: '#000',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                }
                            },
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'จำนวนการอัปโหลดในแต่ละเดือน',
                                font: {
                                    size: 16 // Increased font size for better visibility
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'จำนวนการอัปโหลด',
                                    font: {
                                        size: 14 // Increased font size for better visibility
                                    }
                                },
                                ticks: {
                                    font: {
                                        size: 12 // Reduced font size
                                    }
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'เดือน',
                                    font: {
                                        size: 14 // Increased font size for better visibility
                                    }
                                },
                                ticks: {
                                    font: {
                                        size: 12 // Reduced font size
                                    }
                                }
                            }
                        }
                    }
                });

                // Log the uploads data to the console
                console.log('Uploads Per Month:', uploadsPerMonth);
            }
        });
    </script>
</body>
</html>
