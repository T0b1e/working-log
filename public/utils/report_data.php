<?php
// utils/report_data.php

require_once '../../config/db.php'; // Adjust the path based on your project structure

try {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Create a database connection
    $db = (new Database())->connect();

    // Get the current date
    $currentDate = new DateTime();

    // Update status for overdue projects in progress
    $updateStatusQuery = "
        UPDATE messages
        SET status = 'เลยกำหนด'
        WHERE status = 'กำลังดำเนินการ' AND end_date < :currentDate
    ";
    $updateStatusStmt = $db->prepare($updateStatusQuery);
    $currentDateStr = $currentDate->format('Y-m-d');
    $updateStatusStmt->bindParam(':currentDate', $currentDateStr, PDO::PARAM_STR);
    $updateStatusStmt->execute();

    // Get user role and user ID from cookies
    $user_id = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : null;
    $user_role = isset($_COOKIE['role']) ? $_COOKIE['role'] : null;

    // Initialize conditions
    $conditions = "1=1";
    $params = [];

    // If not admin, fetch only the messages for the specific user
    if ($user_role !== 'admin') {
        $conditions .= " AND messages.user_id = :user_id";
        $params[':user_id'] = $user_id;
    }

    // Fetch total number of messages uploaded
    $totalMessagesQuery = "SELECT COUNT(*) AS total_messages FROM messages WHERE $conditions";
    $stmt = $db->prepare($totalMessagesQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalMessages = $stmt->fetch(PDO::FETCH_ASSOC)['total_messages'] ?? 0;

    // Fetch number of messages with status 'ดำเนินการแล้วเสร็จ'
    $completedMessagesQuery = "SELECT COUNT(*) AS completed_messages FROM messages WHERE status = 'ดำเนินการแล้วเสร็จ' AND $conditions";
    $stmt = $db->prepare($completedMessagesQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $completedMessages = $stmt->fetch(PDO::FETCH_ASSOC)['completed_messages'] ?? 0;

    // Fetch number of messages with status 'เลยกำหนด'
    $overdueMessagesQuery = "SELECT COUNT(*) AS overdue_messages FROM messages WHERE status = 'เลยกำหนด' AND $conditions";
    $stmt = $db->prepare($overdueMessagesQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $overdueMessages = $stmt->fetch(PDO::FETCH_ASSOC)['overdue_messages'] ?? 0;

    // For bar chart, get count of messages for each topic
    $topicsQuery = "
        SELECT title, COUNT(*) AS message_count
        FROM messages
        WHERE $conditions
        GROUP BY title
    ";
    $stmt = $db->prepare($topicsQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $topicsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Define the last three months including the current month dynamically
    $months = [];
    for ($i = 0; $i < 3; $i++) {
        $month = (clone $currentDate)->modify("-$i month");
        $months[] = [
            'name' => $month->format('F'), // e.g., 'November'
            'start_date' => $month->format('Y-m-01'),
            'end_date' => $month->format('Y-m-t')
        ];
    }

    // Extract start and end dates for the entire period (two months ago to current month)
    $overallStartDate = $months[2]['start_date']; // Two months ago
    $overallEndDate = $months[0]['end_date']; // Current month

    // Prepare the SQL query to count uploads per month
    $countQuery = "
        SELECT
            MONTH(created_at) AS month,
            COUNT(*) AS upload_count
        FROM messages
        WHERE created_at BETWEEN :start_date AND :end_date
        GROUP BY month
        ORDER BY month DESC
    ";

    $stmt = $db->prepare($countQuery);
    $stmt->bindParam(':start_date', $overallStartDate, PDO::PARAM_STR);
    $stmt->bindParam(':end_date', $overallEndDate, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize counts
    $uploadCounts = [
        'currentMonth' => 0,
        'previousMonth' => 0,
        'twoMonthsAgo' => 0
    ];

    // Map the results to the uploadCounts array
    foreach ($results as $row) {
        $monthNum = (int)$row['month']; // 1-12
        $count = (int)$row['upload_count'];

        // Determine which month it is
        foreach ($months as $key => $month) {
            $monthDate = new DateTime($month['start_date']);
            if ($monthDate->format('n') == $monthNum) {
                if ($key == 0) {
                    $uploadCounts['currentMonth'] = $count;
                } elseif ($key == 1) {
                    $uploadCounts['previousMonth'] = $count;
                } elseif ($key == 2) {
                    $uploadCounts['twoMonthsAgo'] = $count;
                }
                break;
            }
        }
    }

    // For top 5 users in last 3 months (including current month)
    $startDateOverall = date('Y-m-01', strtotime('-2 months', $currentDate->getTimestamp()));
    $endDateOverall = $currentDate->format('Y-m-t');

    // Modify conditions for the last 3 months
    $lastThreeMonthsCondition = "$conditions AND messages.created_at BETWEEN :startDateOverall AND :endDateOverall";
    $params[':startDateOverall'] = $startDateOverall;
    $params[':endDateOverall'] = $endDateOverall;

    // Top 5 users in the last 3 months
    $topUsersQuery = "
        SELECT users.username, COUNT(messages.message_id) AS message_count
        FROM messages
        JOIN users ON messages.user_id = users.user_id
        WHERE $lastThreeMonthsCondition
        GROUP BY users.username
        ORDER BY message_count DESC
        LIMIT 5
    ";
    $stmt = $db->prepare($topUsersQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $topUsersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch total number of uploads in the last 3 months
    $uploadsLastThreeMonthsQuery = "
        SELECT COUNT(*) AS uploads_last_three_months
        FROM messages
        WHERE $lastThreeMonthsCondition
    ";
    $stmt = $db->prepare($uploadsLastThreeMonthsQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindParam(':startDateOverall', $startDateOverall, PDO::PARAM_STR);
    $stmt->bindParam(':endDateOverall', $endDateOverall, PDO::PARAM_STR);
    $stmt->execute();
    $uploadsLastThreeMonths = $stmt->fetch(PDO::FETCH_ASSOC)['uploads_last_three_months'] ?? 0;

    // Prepare data for JSON response
    $data = [
        'totalMessages' => (int)$totalMessages,
        'completedMessages' => (int)$completedMessages,
        'overdueMessages' => (int)$overdueMessages,
        'topicsData' => $topicsData,
        'topUsersData' => $topUsersData,
        'uploadsPerMonth' => [
            $months[2]['name'] => (int)$uploadCounts['twoMonthsAgo'],
            $months[1]['name'] => (int)$uploadCounts['previousMonth'],
            $months[0]['name'] => (int)$uploadCounts['currentMonth']
        ],
        'uploadsLastThreeMonths' => (int)$uploadsLastThreeMonths
    ];

    // Return data as JSON
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (PDOException $e) {
    // Log the error
    error_log('Database error: ' . $e->getMessage());

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error'
    ]);
}
?>
