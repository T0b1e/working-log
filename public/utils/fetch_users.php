<?php
require_once '../../config/db.php';

try {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Create a database connection
    $db = (new Database())->connect();

    // Get the current date to check overdue status
    $currentDate = date('Y-m-d');

    // Update status for overdue projects in progress
    $updateStatusQuery = "
        UPDATE messages
        SET status = 'เลยกำหนด'
        WHERE status = 'in progress' AND end_date < :currentDate
    ";
    $updateStatusStmt = $db->prepare($updateStatusQuery);
    $updateStatusStmt->bindParam(':currentDate', $currentDate, PDO::PARAM_STR);
    $updateStatusStmt->execute();

    // Get user role and user ID from cookies
    $user_id = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : null;
    $user_role = isset($_COOKIE['role']) ? $_COOKIE['role'] : null;

    // Initialize the base query to count total records (without limit)
    $countQuery = "
    SELECT COUNT(*) AS total_records
    FROM users
    INNER JOIN messages ON users.user_id = messages.user_id
    LEFT JOIN message_files ON messages.message_id = message_files.message_id
    LEFT JOIN files ON message_files.file_id = files.file_id
    WHERE 1=1";

    // If not admin, fetch only the messages for the specific user
    if ($user_role !== 'admin') {
        $countQuery .= " AND users.user_id = :user_id";
    }

    // Check for search criteria and term
    $criteria = isset($_GET['criteria']) ? $_GET['criteria'] : '';
    $term = isset($_GET['term']) ? $_GET['term'] : '';

    if ($criteria && $term) {
        $allowedCriteria = ['username', 'title', 'status', 'created_at', 'file_name'];
        if (in_array($criteria, $allowedCriteria)) {
            $countQuery .= " AND $criteria LIKE :term";
        } else {
            throw new Exception('Invalid search criteria provided.');
        }
    }

    // Prepare and execute the count statement
    $countStmt = $db->prepare($countQuery);
    if ($user_role !== 'admin') {
        $countStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    if ($criteria && $term) {
        $countStmt->bindValue(':term', '%' . $term . '%');
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();

    // Get pagination parameters from the request
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Default limit to 10
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;     // Default page to 1
    $offset = ($page - 1) * $limit;

    // Initialize the base query with limit and offset for data retrieval
    $query = "
    SELECT 
        users.user_id, 
        users.username, 
        messages.message_id, 
        messages.title, 
        messages.description, 
        messages.status, 
        messages.created_at, 
        messages.start_date,     -- Added start_date
        messages.end_date,       -- Added end_date
        files.file_name
    FROM users
    INNER JOIN messages ON users.user_id = messages.user_id
    LEFT JOIN message_files ON messages.message_id = message_files.message_id
    LEFT JOIN files ON message_files.file_id = files.file_id
    WHERE 1=1";

    if ($user_role !== 'admin') {
        $query .= " AND users.user_id = :user_id";
    }

    if ($criteria && $term) {
        $query .= " AND $criteria LIKE :term";
    }

    // Add limit and offset
    if ($limit > 0) {
        $query .= " LIMIT :limit OFFSET :offset";
    }

    // Prepare the statement
    $stmt = $db->prepare($query);

    // Bind parameters
    if ($user_role !== 'admin') {
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    if ($criteria && $term) {
        $stmt->bindValue(':term', '%' . $term . '%');
    }
    if ($limit > 0) {
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    }

    // Execute the query
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return results along with total records for pagination calculation
    echo json_encode([
        'success' => true,
        'data' => $results,
        'totalRecords' => $totalRecords,
        'totalPages' => $limit > 0 ? ceil($totalRecords / $limit) : 1
    ]);

} catch (PDOException $e) {
    // Handle database errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Handle general errors (e.g., invalid search criteria)
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
