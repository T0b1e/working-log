<?php
require_once '../../config/db.php';

try {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Create a database connection
    $db = (new Database())->connect();

    // Get user role and user ID from cookies
    $user_id = isset($_COOKIE['user_id']) ? intval($_COOKIE['user_id']) : null;
    $user_role = isset($_COOKIE['role']) ? $_COOKIE['role'] : null;

    // Define initial query components
    $baseQuery = "
        SELECT 
            users.user_id, 
            users.username, 
            messages.message_id, 
            messages.title, 
            messages.description, 
            messages.status, 
            messages.created_at, 
            messages.start_date, 
            messages.end_date,
            files.file_name
        FROM users
        INNER JOIN messages ON users.user_id = messages.user_id
        LEFT JOIN message_files ON messages.message_id = message_files.message_id
        LEFT JOIN files ON message_files.file_id = files.file_id
        WHERE 1=1
    ";

    // Add user restriction if not an admin
    if ($user_role !== 'admin') {
        $baseQuery .= " AND users.user_id = :user_id";
    }

    // ==============================
    // Handle Search Criteria & Terms
    // ==============================
    $criteria = isset($_GET['criteria']) ? $_GET['criteria'] : '';
    $term = isset($_GET['term']) ? $_GET['term'] : '';
    $allowedCriteria = ['username', 'title', 'description', 'priority', 'status', 'file_name']; // Added 'description'

    if ($criteria && $term && in_array($criteria, $allowedCriteria)) {
        // To prevent SQL injection, ensure that $criteria is a valid column name
        // This is already handled by checking against $allowedCriteria
        $baseQuery .= " AND $criteria LIKE :term";
    }

    // ==============================
    // Handle Date Filters - Only 'day'
    // ==============================
    $dateType = isset($_GET['dateType']) ? $_GET['dateType'] : '';
    $dateValue = isset($_GET['dateValue']) ? $_GET['dateValue'] : '';

    if ($dateType && $dateValue && $dateType === 'day') {
        $baseQuery .= " AND DATE(messages.created_at) = :dateValue";
    }

    // ==============================
    // Handle Pagination Parameters
    // ==============================
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Add LIMIT clause for pagination if a limit is set and not 'ทั้งหมด' (All)
    $paginationQuery = $baseQuery;
    if ($limit > 0) {
        $paginationQuery .= " LIMIT :limit OFFSET :offset";
    }

    // ==============================
    // Prepare and Bind Parameters
    // ==============================
    $stmt = $db->prepare($paginationQuery);

    if ($user_role !== 'admin') {
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    if ($criteria && $term && in_array($criteria, $allowedCriteria)) {
        $stmt->bindValue(':term', '%' . $term . '%', PDO::PARAM_STR);
    }
    if ($dateType && $dateValue && $dateType === 'day') {
        $stmt->bindValue(':dateValue', $dateValue, PDO::PARAM_STR);
    }
    if ($limit > 0) {
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    }

    // Execute the query
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==============================
    // Count Total Records
    // ==============================
    $countQuery = "
        SELECT COUNT(DISTINCT messages.message_id) AS total_records
        FROM users
        INNER JOIN messages ON users.user_id = messages.user_id
        LEFT JOIN message_files ON messages.message_id = message_files.message_id
        LEFT JOIN files ON message_files.file_id = files.file_id
        WHERE 1=1
    ";

    if ($user_role !== 'admin') {
        $countQuery .= " AND users.user_id = :user_id";
    }
    if ($criteria && $term && in_array($criteria, $allowedCriteria)) {
        $countQuery .= " AND $criteria LIKE :term";
    }
    if ($dateType && $dateValue && $dateType === 'day') {
        $countQuery .= " AND DATE(messages.created_at) = :dateValue";
    }

    $countStmt = $db->prepare($countQuery);

    if ($user_role !== 'admin') {
        $countStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    if ($criteria && $term && in_array($criteria, $allowedCriteria)) {
        $countStmt->bindValue(':term', '%' . $term . '%', PDO::PARAM_STR);
    }
    if ($dateType && $dateValue && $dateType === 'day') {
        $countStmt->bindValue(':dateValue', $dateValue, PDO::PARAM_STR);
    }

    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();

    // ==============================
    // Calculate Total Pages
    // ==============================
    if ($limit > 0) {
        $totalPages = ceil($totalRecords / $limit);
    } else {
        $totalPages = 1; // If limit is 0 (All), only one page is needed
    }

    // ==============================
    // Return JSON Response
    // ==============================
    echo json_encode([
        'success' => true,
        'data' => $results,
        'totalRecords' => $totalRecords,
        'totalPages' => $totalPages
    ]);

} catch (PDOException $e) {
    // Database error handling
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // General error handling
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
