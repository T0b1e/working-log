<?php
require_once '../config/db.php';

class Message {
    private $conn;
    private $table = 'messages';

    public $message_id;
    public $user_id;
    public $title;
    public $description;
    public $priority;
    public $status;
    public $body;
    public $created_at;
    public $updated_at;

    // Constructor to initialize the database connection
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Create a new message
    public function create() {
        $query = "INSERT INTO $this->table (user_id, title, description, priority, status, body) 
                  VALUES (:user_id, :title, :description, :priority, :status, :body)";
        $stmt = $this->conn->prepare($query);

        // Sanitize and bind data
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':priority', $this->priority);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':body', $this->body);

        return $stmt->execute();
    }

    // Read message by ID
    public function read($message_id) {
        $query = "SELECT * FROM $this->table WHERE message_id = :message_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':message_id', $message_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update message details
    public function update() {
        $query = "UPDATE $this->table 
                  SET title = :title, description = :description, priority = :priority, status = :status, body = :body 
                  WHERE message_id = :message_id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':priority', $this->priority);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':body', $this->body);
        $stmt->bindParam(':message_id', $this->message_id);

        return $stmt->execute();
    }

    // Delete message
    public function delete($message_id) {
        $query = "DELETE FROM $this->table WHERE message_id = :message_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':message_id', $message_id);

        return $stmt->execute();
    }
}
?>
