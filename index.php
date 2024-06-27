<?php
require 'shortener.php';

$request_uri = trim($_SERVER['REQUEST_URI'], '/');
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['url'])) {
    $original_url = $_POST['url'];
    if (!isValidUrl($original_url)) {
        echo json_encode(['error' => 'Invalid URL. Please enter a valid URL with a proper protocol (e.g., http, https) and a domain extension (e.g., .com, .net, .org)']);
        exit();
    }
    $short_code = checkExistingUrl($original_url);
    if (!$short_code) {
        $short_code = shortenUrl($original_url);
        if ($short_code === false) {
            error_log("Error shortening URL: $original_url");
            echo json_encode(['error' => 'There was an error processing your request. Please try again later.']);
        } else {
            echo json_encode(['shortened_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/' . $short_code]);
        }
    } else {
        echo json_encode(['shortened_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/' . $short_code]);
    }
    exit();
}

if ($request_uri && $_SERVER['REQUEST_METHOD'] != 'POST') {
    $original_url = checkExistingUrlByCode($request_uri);
    if ($original_url) {
        header("Location: " . $original_url);
        exit();
    } else {
        $error_message = "URL not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URL Shortener</title>
    <link rel="apple-touch-icon" sizes="180x180" href="https://sachi.lk/assets/img/fav/apple-touch-icon.png?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="https://sachi.lk/assets/img/fav/favicon-32x32.png?v=2">
    <link rel="icon" type="image/png" sizes="192x192" href="https://sachi.lk/assets/img/fav/android-chrome-192x192.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="https://sachi.lk/assets/img/fav/favicon-16x16.png?v=2">
    <link rel="mask-icon" href="https://sachi.lk/assets/img/fav/safari-pinned-tab.svg?v=2" color="#8E94F2">
    <link rel="shortcut icon" href="https://sachi.lk/assets/img/fav/favicon.ico?v=2">
    <meta name="theme-color" content="#191919">
    <meta property="og:type" content="website">
    <meta property="og:title" content="URL Shortener">
    <meta property="og:description" content="URL Shortener">
    <meta property="og:url" content="https://cdn.lk">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.lk/assets/css/style.css?v=0.0.9g">
</head>
<body>
	<a href="https://cdn.lk" class="imgLK"><img src="https://cdn.lk/assets/img/scissor.png?v=1b"> dot L K</a>
	<div class="outer-container">
    <div class="container">
        <form id="shorten-form">
            <input type="text" name="url" placeholder="Enter URL to shorten" required>
            <button type="submit">Shorten</button>
        </form>
        <div id="result"></div>
        <?php if ($error_message): ?>
            <div id="error"><?= $error_message ?></div>
        <?php endif; ?>
    </div>
    <a href="https://cdn.lk/privacy/" style="color: #8E94F2;text-decoration: none;" class="on-side">privacy policy</a>
    </div>
    <div id="footer">
        <p>made with <i class="fas fa-heart" style="color: #FF007F;font-size: 36px;"></i> by <a href="https://sachi.lk" style="color: #8E94F2;text-decoration: none;font-weight: bold;">sachi</a></p>
    </div>
    <script>
        document.getElementById('shorten-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('result').innerHTML = `<p class="error">${data.error}</p>`;
                } else {
                    document.getElementById('result').innerHTML = `
                        <p>Shortened URL: <a href="${data.shortened_url}" target="_blank">${data.shortened_url}</a></p>
                        <button id="copy-button" data-url="${data.shortened_url}">Copy URL</button>
                    `;
                    document.getElementById('copy-button').addEventListener('click', function() {
                        const url = this.getAttribute('data-url');
                        navigator.clipboard.writeText(url).then(function() {
                            alert('Shortened URL copied to the clipboard');
                        }, function(err) {
                            console.error('Could not copy text: ', err);
                        });
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('result').innerHTML = `<p class="error">An error occurred. Please try again.</p>`;
            });
        });
    </script>
</body>
</html>