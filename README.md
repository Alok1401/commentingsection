# Multilingual Comment System 💬🌐

A safe, accessible, and user-friendly **multilingual comment system** with real-time translation, likes/dislikes, auto-moderation, and city display.

![Version](https://img.shields.io/badge/Version-1.0-8B5CF6?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.0+-06D6A0?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-FF3CAC?style=for-the-badge&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-2B86C5?style=for-the-badge)

## ✨ Features

- 🌐 **Multilingual Translation** — Translate comments to 20+ languages (MyMemory API)
- 👍👎 **Like / Dislike System** — One per user, mutual exclusion
- 🛡️ **Auto Moderation** — Comments auto-removed after 2 dislikes
- 📍 **City Display** — Shows commenter's city via IP geolocation
- 🚫 **Special Character Blocking** — Spam & unwanted chars blocked automatically
- 🎨 **Ultra Premium UI** — Aurora background, glassmorphism, neon glows, 3D animations
- 📊 **Admin Dashboard** — View, search, moderate, and restore comments
- 🔒 **Security** — XSS protection, SQL injection prevention, rate limiting

## 🚀 Quick Setup

### Prerequisites
- PHP 8.0+ (XAMPP/WAMP/LAMP)
- MySQL 5.7+
- Apache Server

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/YOUR_USERNAME/commentingsection.git
```

2. **Copy to your web server**
```bash
sudo cp -R commentingsection /Applications/XAMPP/xamppfiles/htdocs/
```

3. **Start Apache & MySQL** in XAMPP

4. **Run Database Setup**
```
http://localhost/commentingsection/setup.php
```

5. **Open the App**
```
http://localhost/commentingsection/index.html
```

6. **Admin Dashboard**
```
http://localhost/commentingsection/admin/index.html
```

## 📁 Project Structure

```
commentingsection/
├── config/database.php       # DB connection + CORS
├── api/
│   ├── comments.php          # CRUD REST API
│   ├── like.php              # Like toggle
│   ├── dislike.php           # Dislike + auto-moderation
│   ├── translate.php         # Translation endpoint
│   ├── moderation.php        # Admin moderation
│   └── location.php          # IP geolocation
├── includes/
│   ├── Comment.php           # Comment model
│   ├── Moderator.php         # Auto-moderation logic
│   └── Translator.php        # Translation + caching
├── admin/index.html          # Admin dashboard
├── assets/css/style.css      # Premium dark UI
├── assets/css/admin.css      # Admin styles
├── assets/js/app.js          # Main SPA logic
├── assets/js/admin.js        # Admin logic
├── index.html                # Main entry point
└── setup.php                 # One-click DB setup
```

## 🌍 Supported Languages

English, Hindi, Spanish, French, German, Arabic, Chinese, Japanese, Korean, Portuguese, Russian, Italian, Turkish, Dutch, Swedish, Polish, Thai, Vietnamese, Indonesian, Malay

## 🔑 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/comments.php` | List comments (paginated) |
| `POST` | `/api/comments.php` | Create comment |
| `DELETE` | `/api/comments.php?id=1` | Remove comment |
| `POST` | `/api/like.php` | Toggle like |
| `POST` | `/api/dislike.php` | Toggle dislike |
| `POST` | `/api/translate.php` | Translate comment |
| `GET` | `/api/moderation.php` | Get moderation logs |
| `POST` | `/api/moderation.php` | Moderate comment |
| `GET` | `/api/location.php` | Get user location |

## 👨‍💻 Built By

**Alok Chauhan** — Full Stack Developer

## 📄 License

This project is licensed under the MIT License.
