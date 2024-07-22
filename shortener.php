<?php
require 'database/db.php';

function generateShortCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function isValidUrl($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $pattern = '/^https?:\/\/(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}(?:\/|$)/i';
    return preg_match($pattern, $url);
}

function normalizeUrl($url) {
    // Remove protocol and trailing slashes
    $url = preg_replace('/^https?:\/\//', '', rtrim($url, '/'));
    return $url;
}

function generateUniqueShortCode($length = 6) {
    global $conn;
    $unique = false;
    $short_code = '';
    while (!$unique) {
        $short_code = generateShortCode($length);
        $stmt = $conn->prepare("SELECT id FROM ShortenedURL WHERE short_code = ?");
        if (!$stmt) {
            error_log("Error preparing statement: " . $conn->error);
            return false;
        }
        $stmt->bind_param("s", $short_code);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $unique = true;
        }
        $stmt->close();
    }
    return $short_code;
}

function check_url_with_virustotal($url) {
    $apiKey = 'API_KEY';
    $apiUrl = 'https://www.virustotal.com/vtapi/v2/url/report';
    $params = [
        'apikey' => $apiKey,
        'resource' => $url
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return isset($result['positives']) && $result['positives'] > 0;
}

function shortenUrl($original_url) {
    $normalized_url = normalizeUrl($original_url);
    if (!isValidUrl($original_url)) {
        error_log("Invalid URL: $original_url");
        return false;
    }
    if (check_url_with_virustotal($original_url)) {
        error_log("Malicious URL detected: $original_url");
        return false;
    }
    global $conn;
    $short_code = generateUniqueShortCode();
    if ($short_code === false) {
        return false;
    }
    $stmt = $conn->prepare("INSERT INTO ShortenedURL (original_url, normalized_url, short_code) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log("Error preparing insert statement: " . $conn->error);
        return false;
    }
    $stmt->bind_param("sss", $original_url, $normalized_url, $short_code);
    if (!$stmt->execute()) {
        error_log("Error executing insert statement: " . $stmt->error);
        return false;
    }
    $stmt->close();
    return $short_code;
}

function checkExistingUrl($original_url) {
    $normalized_url = normalizeUrl($original_url);
    global $conn;
    $stmt = $conn->prepare("SELECT short_code FROM ShortenedURL WHERE normalized_url = ?");
    if (!$stmt) {
        error_log("Error preparing select statement: " . $conn->error);
        return false;
    }
    $stmt->bind_param("s", $normalized_url);
    $stmt->execute();
    $stmt->bind_result($short_code);
    $stmt->fetch();
    $stmt->close();
    return $short_code ? $short_code : false;
}

function checkExistingUrlByCode($short_code) {
    global $conn;
    $stmt = $conn->prepare("SELECT original_url FROM ShortenedURL WHERE short_code = ?");
    if (!$stmt) {
        error_log("Error preparing select statement: " . $conn->error);
        return false;
    }
    $stmt->bind_param("s", $short_code);
    $stmt->execute();
    $stmt->bind_result($original_url);
    $stmt->fetch();
    $stmt->close();
    return $original_url ? $original_url : false;
}

function redirectToUrl($short_code) {
    global $conn;
    $stmt = $conn->prepare("SELECT original_url FROM ShortenedURL WHERE short_code = ?");
    if (!$stmt) {
        error_log("Error preparing select statement: " . $conn->error);
        echo "Error: Could not prepare statement";
        return;
    }
    $stmt->bind_param("s", $short_code);
    $stmt->execute();
    $stmt->bind_result($original_url);
    $stmt->fetch();
    $stmt->close();

    if ($original_url) {
        header("Location: " . $original_url);
        exit();
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "URL not found!";
    }
}
?>