<?php
require_once '../config/db.php';

class MessageFile {
    private $conn;
    private $table = 'message_files';

    public $message_id;
    public $file_id;

    // Constructor to initialize the database connection
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Link a file to a message
    public function linkFileToMessage() {
        $query = "INSERT INTO $this->table (message_id, file_id) 
                  VALUES (:message_id, :file_id)";
        $stmt = $this->conn->prepare($query);

        // Sanitize and bind data
        $stmt->bindParam(':message_id', $this->message_id);
        $stmt->bindParam(':file_id', $this->file_id);

        return $stmt->execute();
    }

    // Unlink file from message
    public function unlinkFileFromMessage() {
        $query = "DELETE FROM $this->table WHERE message_id = :message_id AND file_id = :file_id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':message_id', $this->message_id);
        $stmt->bindParam(':file_id', $this->file_id);

        return $stmt->execute();
    }
}
?>
