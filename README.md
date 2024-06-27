## cut.lk

Welcome to the URL Shortener project! This repository contains the code for a URL shortening service that allows users to create shortened URLs that redirect to longer, original URLs. This service is designed to be simple, efficient, and secure, ensuring that your long URLs are easily shareable and manageable.

### Purpose

The primary goal of this project is to provide a robust and scalable solution for URL shortening, which can be used by individuals and businesses alike. The service makes it easier to manage and share links.

### Features

- **URL Shortening**: Convert long URLs into short, easy-to-share links.
- **Redirection**: Automatically redirect users from the shortened URL to the original URL.
- **Cloudflare Integration**: Utilize Cloudflare's security features for enhanced protection.

### Technology Stack

- **Backend**: PHP
- **Database**: MySQL
- **Security**: Cloudflare

### How It Works

1. **Submit URL**: Users submit a long URL to the service.
2. **Generate Short Code**: The service generates a unique short code for the URL.
3. **Store and Redirect**: The original URL and short code are stored in the database. When the short URL is accessed, the service redirects to the original URL.


**Clone the Repository**: 
   ```bash
   git clone https://github.com/saacki/cut.lk.git
