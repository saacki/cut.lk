<?php
$flag_file = '../install.lock';

if (file_exists($flag_file)) {
    header('Location: https://cdn.lk/403.html');
    exit();
}

require '../database/db.php';

// Create table if it doesn't exist
$tableCreationQuery = "
CREATE TABLE IF NOT EXISTS ShortenedURL (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_url TEXT NOT NULL,
    short_code VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($tableCreationQuery) === FALSE) {
    die("Error creating table: " . $conn->error);
}

// Alter short_code column to be case-sensitive
$alterColumnQuery = "
ALTER TABLE ShortenedURL 
MODIFY short_code VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL UNIQUE";

if ($conn->query($alterColumnQuery) === FALSE) {
    die("Error altering short_code column: " . $conn->error);
}

// Check if normalized_url column exists and add it if it doesn't
$checkColumnQuery = "SHOW COLUMNS FROM ShortenedURL LIKE 'normalized_url'";
$result = $conn->query($checkColumnQuery);

if ($result->num_rows == 0) {
    $addColumnQuery = "ALTER TABLE ShortenedURL ADD normalized_url VARCHAR(255) NOT NULL";
    if ($conn->query($addColumnQuery) === FALSE) {
        die("Error adding normalized_url column: " . $conn->error);
    }

    // Backfill normalized_url for existing records
    $backfillQuery = "SELECT id, original_url FROM ShortenedURL";
    $result = $conn->query($backfillQuery);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $original_url = $row['original_url'];
            $normalized_url = preg_replace('/^https?:\/\//', '', rtrim($original_url, '/'));

            $updateQuery = $conn->prepare("UPDATE ShortenedURL SET normalized_url = ? WHERE id = ?");
            $updateQuery->bind_param("si", $normalized_url, $id);
            if (!$updateQuery->execute()) {
                error_log("Error updating record ID $id: " . $updateQuery->error);
            }
            $updateQuery->close();
        }
    }
}

echo "Installation tasks completed successfully.";

// Create the flag file to indicate the script has been run
file_put_contents($flag_file, 'Installation complete.');

$conn->close();
?>
