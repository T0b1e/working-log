<?php

// Start output buffering
ob_start();

// Load the environment variables (if you are using .env)
require_once '../../config/db.php';

// Initialize the database connection
$dbConnection = (new Database())->connect();

// End output buffering and flush the output
ob_end_flush();
