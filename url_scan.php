<?php
require 'shortener.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_url = $_POST['url'];
    
    if (isValidUrl($test_url)) {
        $isMalicious = check_url_with_virustotal($test_url);

        if ($isMalicious) {
            $message = "The URL is detected as malicious.";
        } else {
            $message = "The URL is not detected as malicious.";
        }
    } else {
        $message = "Invalid URL format.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan URL</title>
</head>
<body>
    <h1>Scan URL for Malware/Phishing</h1>
    <form method="POST" action="url_scan.php">
        <label for="url">Enter URL:</label>
        <input type="text" id="url" name="url" required>
        <button type="submit">Scan URL</button>
    </form>
    
    <?php if (isset($message)): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
</body>
</html>
