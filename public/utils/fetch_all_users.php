<?php
require_once '../../config/db.php';

try {
    // Create a database connection
    $db = (new Database())->connect();

    // Fetch user data (user_id, username, email, role, department, address, phone)
    $query = "
    SELECT 
        user_id, 
        username, 
        email, 
        role, 
        department, 
        address, 
        phone 
    FROM users
";

    $stmt = $db->prepare($query);
    $stmt->execute();

    // Fetch all results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log successful fetch (optional for debugging)
    error_log("Fetched users: " . json_encode($results));

    // Return results as JSON
    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
