<?php
$flag_file = '../repair.lock';

if (file_exists($flag_file)) {
    header('Location: https://cut.lk/403.html');
    exit();
}

require '../database/db.php';

// Ensure the short_code column is case-sensitive and unique
$alterShortCodeQuery = "
ALTER TABLE ShortenedURL 
MODIFY short_code VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL UNIQUE";

if ($conn->query($alterShortCodeQuery) === FALSE) {
    die("Error altering short_code column: " . $conn->error);
}

// Ensure the original_url column is of type TEXT
$alterOriginalUrlQuery = "
ALTER TABLE ShortenedURL 
MODIFY original_url TEXT NOT NULL";

if ($conn->query($alterOriginalUrlQuery) === FALSE) {
    die("Error altering original_url column: " . $conn->error);
}

// Check if normalized_url column exists and add it if it doesn't
$checkColumnQuery = "SHOW COLUMNS FROM ShortenedURL LIKE 'normalized_url'";
$result = $conn->query($checkColumnQuery);

if ($result->num_rows == 0) {
    $addColumnQuery = "ALTER TABLE ShortenedURL ADD normalized_url TEXT NOT NULL";
    if ($conn->query($addColumnQuery) === FALSE) {
        die("Error adding normalized_url column: " . $conn->error);
    }
}

// Backfill normalized_url for existing records
$backfillQuery = "SELECT id, original_url FROM ShortenedURL WHERE normalized_url IS NULL OR normalized_url = ''";
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

echo "Repair tasks completed successfully.";

// Create the flag file to indicate the script has been run
file_put_contents($flag_file, 'Repair complete.');

$conn->close();
?>