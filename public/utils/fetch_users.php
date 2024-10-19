<?php
require_once '../../config/db.php';

try {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Create a database connection
    $db = (new Database())->connect();

    // Get user role and user ID from cookies
    $user_id = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : null;
    $user_role = isset($_COOKIE['role']) ? $_COOKIE['role'] : null;

    // Initialize the base query
    $query = "
    SELECT 
        users.user_id, 
        users.username, 
        messages.message_id, 
        messages.title, 
        messages.description, 
        messages.status, 
        messages.created_at, 
        files.file_name
    FROM users
    INNER JOIN messages ON users.user_id = messages.user_id
    LEFT JOIN message_files ON messages.message_id = message_files.message_id
    LEFT JOIN files ON message_files.file_id = files.file_id
    WHERE 1=1";

    // Check user role and adjust query accordingly
    if ($user_role !== 'admin') {
        // If not admin, fetch only the messages for the specific user
        $query .= " AND users.user_id = :user_id";
    }

    // Check for search criteria and term
    $criteria = isset($_GET['criteria']) ? $_GET['criteria'] : '';
    $term = isset($_GET['term']) ? $_GET['term'] : '';

    // Prepare statement for search
    if ($criteria && $term) {
        // Allowed criteria to prevent SQL injection
        $allowedCriteria = ['username', 'title', 'status', 'created_at', 'file_name'];
        if (in_array($criteria, $allowedCriteria)) {
            $query .= " AND $criteria LIKE :term";
        } else {
            throw new Exception('Invalid search criteria provided.');
        }
    }

    // Prepare the statement
    $stmt = $db->prepare($query);

    // Bind the user_id if the user is not an admin
    if ($user_role !== 'admin') {
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }

    // Bind the search term if criteria is set
    if ($criteria && $term) {
        $stmt->bindValue(':term', '%' . $term . '%');
    }

    $stmt->execute();

    // Fetch all results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if there are no results
    if (empty($results)) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'No data found matching the criteria.'
        ]);
        exit();
    }

    // Return results as JSON
    echo json_encode([
        'success' => true,
        'data' => $results
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
