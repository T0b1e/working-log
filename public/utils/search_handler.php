<?php
require_once '../../config/db.php';

try {
    $db = (new Database())->connect();

    // Get search criteria and term from the query string
    $criteria = $_GET['criteria'];
    $term = $_GET['term'];

    // Validate input
    $validCriteria = ['username', 'title', 'priority', 'status', 'created_at', 'file_name'];
    if (!in_array($criteria, $validCriteria)) {
        echo json_encode(['success' => false, 'message' => 'Invalid search criteria']);
        exit();
    }

    // Prepare the SQL query based on the selected criteria
    $query = "SELECT * FROM messages WHERE $criteria LIKE :term"; // Make sure these fields exist in your table
    $stmt = $db->prepare($query);
    $stmt->bindValue(':term', '%' . $term . '%');
    $stmt->execute();

    // Fetch results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return results as JSON
    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
