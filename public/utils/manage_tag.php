<?php
require_once '../../config/db.php';

try {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Create a database connection
    $db = (new Database())->connect();

    // Check if the action is to add, edit, or delete a tag (priority, status, or title)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];

        // Handle title addition
        if (isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'add_priority') {
                $stmt = $db->prepare("INSERT INTO priority (name) VALUES (:name)");
                $stmt->bindParam(':name', $name);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Priority added successfully']);
            } elseif ($action === 'add_status') {
                $stmt = $db->prepare("INSERT INTO status (name) VALUES (:name)");
                $stmt->bindParam(':name', $name);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Status added successfully']);
            } elseif ($action === 'add_title') {
                $stmt = $db->prepare("INSERT INTO title (name) VALUES (:name)");
                $stmt->bindParam(':name', $name);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Title added successfully']);
            }
        }

        // Handle edit actions
        if (isset($_POST['edit_action'])) {
            $edit_action = $_POST['edit_action'];
            $id = $_POST['id'];

            if ($edit_action === 'edit_priority') {
                $stmt = $db->prepare("UPDATE priority SET name = :name WHERE id = :id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Priority updated successfully']);
            } elseif ($edit_action === 'edit_status') {
                $stmt = $db->prepare("UPDATE status SET name = :name WHERE id = :id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            } elseif ($edit_action === 'edit_title') {
                $stmt = $db->prepare("UPDATE title SET name = :name WHERE id = :id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Title updated successfully']);
            }
        }

        // Handle delete actions
        if (isset($_POST['delete_action'])) {
            $delete_action = $_POST['delete_action'];
            $id = $_POST['id'];

            if ($delete_action === 'delete_priority') {
                $stmt = $db->prepare("DELETE FROM priority WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Priority deleted successfully']);
            } elseif ($delete_action === 'delete_status') {
                $stmt = $db->prepare("DELETE FROM status WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Status deleted successfully']);
            } elseif ($delete_action === 'delete_title') {
                $stmt = $db->prepare("DELETE FROM title WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Title deleted successfully']);
            }
        }
    } else {
        // Handle GET request to fetch all priorities, statuses, and titles
        $priorities = $db->query("SELECT * FROM priority")->fetchAll(PDO::FETCH_ASSOC);
        $statuses = $db->query("SELECT * FROM status")->fetchAll(PDO::FETCH_ASSOC);
        $titles = $db->query("SELECT * FROM title")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'priorities' => $priorities, 'statuses' => $statuses, 'titles' => $titles]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
