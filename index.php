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
    if (check_url_with_virustotal($original_url)) {
        echo json_encode(['error' => 'The provided URL is potentially unsafe!']);
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
    <link rel="apple-touch-icon" sizes="180x180" href="https://cut.lk/assets/img/fav/apple-touch-icon.png?v=2a">
    <link rel="icon" type="image/png" sizes="32x32" href="https://cut.lk/assets/img/fav/favicon-32x32.png?v=2a">
    <link rel="icon" type="image/png" sizes="192x192" href="https://cut.lk/assets/img/fav/android-chrome-192x192.png?v=2a">
    <link rel="icon" type="image/png" sizes="16x16" href="https://cut.lk/assets/img/fav/favicon-16x16.png?v=2a">
    <link rel="manifest" href="https://cut.lk/assets/img/fav/site.webmanifest?v=2a">
    <link rel="mask-icon" href="https://cut.lk/assets/img/fav/safari-pinned-tab.svg?v=2a" color="#533A71">
    <link rel="shortcut icon" href="https://cut.lk/assets/img/fav/favicon.ico?v=2a">
    <meta name="theme-color" content="#533A71">
    <meta property="og:type" content="website">
    <meta property="og:title" content="URL Shortener">
    <meta property="og:description" content="URL Shortener">
    <meta property="og:url" content="https://cut.lk">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cut.lk/assets/css/style.css?v=0.1.0a">
</head>
<body>
    <a href="https://cut.lk" id="imgLK"><img src="https://cut.lk/assets/img/scissor.png?v=1b"><strong> dot L K</strong></a>
    <div class="outer-container">
        <div class="container">
            <form id="shorten-form">
                <input type="text" name="url" placeholder="Enter URL to shorten" required>
                <button class="bluebutton" type="submit">shorten</button>
            </form>
            <div id="result"></div>
            <?php if ($error_message): ?>
                <div id="error"><?= $error_message ?></div>
            <?php endif; ?>
        </div>
    </div>
    <p id="links"><a href="#" style="color: #fff;text-decoration: none;" >API</a>&nbsp&nbsp |&nbsp&nbsp <a href="https://github.com/saacki/cut.lk" style="color: #fff;text-decoration: none;" >GitHub</a>&nbsp&nbsp |&nbsp&nbsp <a href="https://cut.lk/privacy/" style="color: #fff;text-decoration: none;" >Privacy Policy</a></p>
    <div id="footer">
        <p>made with <i class="fas fa-heart" style="color: #FF007F;font-size: 24px;"></i> by <a href="https://sachi.lk" style="color: #8E94F2;text-decoration: none;font-weight: bold;">sachi</a></p>
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
                        <button id="copy-button" class="Btn" data-url="${data.shortened_url}"><span class="text">copy</span><span class="svgIcon"><svg fill="white" viewBox="0 0 384 512" height="1em" xmlns="http://www.w3.org/2000/svg"><path d="M280 64h40c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128C0 92.7 28.7 64 64 64h40 9.6C121 27.5 153.3 0 192 0s71 27.5 78.4 64H280zM64 112c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320c8.8 0 16-7.2 16-16V128c0-8.8-7.2-16-16-16H304v24c0 13.3-10.7 24-24 24H192 104c-13.3 0-24-10.7-24-24V112H64zm128-8a24 24 0 1 0 0-48 24 24 0 1 0 0 48z"></path></svg></span></button>
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