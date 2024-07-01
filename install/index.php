<?php
$flag_file = '../install.lock';

if (file_exists($flag_file)) {
    header('Location: https://cut.lk/403.html');
    exit();
}

require '../database/db.php';

// Create table if it doesn't exist
$tableCreationQuery = "
CREATE TABLE IF NOT EXISTS ShortenedURL (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_url TEXT NOT NULL,
    short_code VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL UNIQUE,
    normalized_url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($tableCreationQuery) === FALSE) {
    die("Error creating table: " . $conn->error);
}

echo "Installation tasks completed successfully.";

// Create the flag file to indicate the script has been run
file_put_contents($flag_file, 'Installation complete.');

$conn->close();
?>