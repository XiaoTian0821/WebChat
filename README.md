# WebConnect — Web-Based Chat, Voice & Video Communication System

A modern, full-featured web communication platform built with PHP 8.3+, MySQL, HTML5, CSS3, and Vanilla JavaScript. Supports private messaging, group chats, voice/video calls (WebRTC), file sharing, community forums, and administrative management.

## Features

- **User Authentication** — Secure registration, login, session management
- **Private Messaging** — Real-time text chat with read receipts
- **Group Chats** — Multi-member conversations with roles (Owner/Admin/Member)
- **Voice Messages** — Record and send audio messages via MediaRecorder API
- **File & Image Sharing** — Upload and share documents and images
- **Emoji Reactions** — React to messages with emoji
- **Message Replies** — Reply to specific messages
- **Typing Indicators** — See when someone is typing
- **Online/Offline Status** — Track user presence
- **Voice Calls** — Peer-to-peer voice calling via WebRTC
- **Video Calls** — Real-time video communication via WebRTC
- **Friend System** — Search, send/receive friend requests
- **User Blocking** — Block/unblock users
- **Community Forums** — Discussion boards with posts, comments, likes
- **Notifications** — In-app notification system
- **Admin Panel** — User management, forum management, report review

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.3+ |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| UI Framework | Bootstrap 5 |
| Icons | Font Awesome 6 |
| Real-time | WebRTC (calls), AJAX Polling (messaging) |
| Security | PDO prepared statements, CSRF tokens, password_hash |

## Requirements

- **Local Development:**
  - XAMPP (Apache + MySQL)
  - PHP 8.3+
  - Modern browser (Chrome, Firefox, Edge, Safari)

- **Production:**
  - cPanel or similar hosting
  - Apache with mod_rewrite
  - MySQL/MariaDB
  - **HTTPS required for WebRTC (voice/video calls)**

## Installation (Local - XAMPP)

1. **Copy the project** to your XAMPP htdocs folder:
   ```
   C:\xampp\htdocs\WebChat\
   ```

2. **Start services:**
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL

3. **Create the database:**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Create a new database named `webconnect`
   - Import the SQL file: `database/database.sql`

4. **Configure the application:**
   - Open `includes/config.php`
   - Update database credentials if needed:
     ```php
     define('DB_HOST', '127.0.0.1');
     define('DB_NAME', 'webconnect');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```
   - Update the app URL:
     ```php
     define('APP_URL', 'http://localhost/WebChat');
     ```

5. **Set permissions** — Ensure the `uploads/` directory and subdirectories are writable:
   ```bash
   chmod 755 uploads/
   chmod 777 uploads/avatars uploads/images uploads/files uploads/voices uploads/groups
   ```

6. **Access the application:**
   - Open your browser and go to: http://localhost/WebChat

### Demo Credentials (DEMO ONLY)

> ⚠️ **Change these passwords before deploying to production!**

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| User 1 | `alice` | `password123` |
| User 2 | `bob` | `password123` |
| User 3 | `charlie` | `password123` |

## cPanel Deployment

1. **Create a database:**
   - Go to phpMyAdmin in cPanel
   - Create a new database (e.g., `username_webconnect`)
   - Create a database user and assign privileges
   - Import `database/database.sql`

2. **Upload files:**
   - Use File Manager or FTP to upload all files to your public_html or subdirectory
   - Ensure the `uploads/` directory and subdirectories are writable (chmod 777)

3. **Configure:**
   - Edit `includes/config.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'your_database_name');
     define('DB_USER', 'your_database_user');
     define('DB_PASS', 'your_database_password');
     define('APP_URL', 'https://yourdomain.com/WebChat');
     ```

4. **Enable HTTPS:**
   - WebRTC (voice/video calls) requires HTTPS in production
   - Enable SSL in cPanel and update APP_URL to use https://

5. **Test:**
   - Register a new account
   - Test login, messaging, file uploads
   - Test voice/video calls (ensure HTTPS is active)

## WebRTC Notes

For voice and video calls to work:

1. **HTTPS is required** for non-localhost deployments
2. **Browser permissions** — Users must allow microphone (and camera for video) access
3. **STUN servers** are configured by default (Google's public STUN)
4. **TURN servers** may be needed for users behind strict firewalls/NAT. To add a TURN server, edit `assets/js/calls.js`:
   ```javascript
   const rtcConfig = {
       iceServers: [
           { urls: "stun:stun.l.google.com:19302" },
           { urls: "turn:your-turn-server.com:3478", username: "user", credential: "pass" }
       ]
   };
   ```

## Project Structure

```
WebChat/
├── index.php                 # Home page
├── login.php                 # User login
├── register.php              # User registration
├── logout.php                # User logout
├── chat.php                  # Private chat interface
├── friends.php               # Friend management
├── groups.php                # Group listing
├── group_chat.php            # Group chat interface
├── forums.php                # Community forums
├── notifications.php         # Notifications page
├── profile.php               # User profile
├── admin/                    # Admin panel
│   ├── login.php
│   ├── index.php
│   ├── users.php
│   ├── forums.php
│   ├── reports.php
│   └── logout.php
├── api/                      # REST API endpoints
│   ├── auth.php
│   ├── chat.php
│   ├── messages.php
│   ├── friends.php
│   ├── groups.php
│   ├── calls.php
│   ├── media.php
│   ├── notifications.php
│   ├── presence.php
│   ├── profile.php
│   ├── reactions.php
│   ├── forums.php
│   └── blocks.php
├── includes/                 # Core PHP libraries
│   ├── config.php
│   ├── database.php
│   ├── auth.php
│   ├── security.php
│   ├── functions.php
│   ├── bootstrap.php
│   ├── layout_start.php
│   └── layout_end.php
├── assets/
│   ├── css/app.css           # Main stylesheet
│   └── js/
│       ├── app.js            # Core utilities
│       ├── chat.js           # Chat functionality
│       ├── calls.js          # WebRTC calling
│       ├── friends.js        # Friends management
│       ├── groups.js         # Group management
│       ├── forums.js         # Forum functionality
│       ├── notifications.js  # Notification handling
│       └── profile.js        # Profile interactions
├── uploads/                  # Uploaded files (writable)
│   ├── avatars/
│   ├── images/
│   ├── files/
│   ├── voices/
│   └── groups/
└── database/
    └── database.sql          # Database schema
```

## Security Features

- **Password hashing** using PHP `password_hash()` with bcrypt
- **SQL injection prevention** via PDO prepared statements
- **XSS protection** with `htmlspecialchars()` output escaping
- **CSRF tokens** on all state-changing forms
- **Session security** with regeneration, HTTP-only cookies, SameSite policy
- **File upload validation** (MIME type, extension, size)
- **Secure media delivery** through authenticated API endpoint
- **Server-side authorization** for all sensitive operations
- **Rate limiting** on login attempts

## Database Tables

| Table | Purpose |
|-------|---------|
| `users` | User accounts and profiles |
| `admins` | Admin accounts |
| `friend_requests` | Pending friend requests |
| `friendships` | Accepted friendships |
| `user_blocks` | User blocks |
| `messages` | Private messages |
| `message_reactions` | Message emoji reactions |
| `message_read_receipts` | Read status tracking |
| `typing_indicators` | Typing status |
| `group_chats` | Group conversations |
| `group_members` | Group membership & roles |
| `group_messages` | Group messages |
| `voice_call_sessions` | Call session tracking |
| `voice_call_signals` | WebRTC signaling data |
| `notifications` | User notifications |
| `forums` | Forum categories |
| `forum_posts` | Forum posts |
| `post_comments` | Post comments |
| `post_likes` | Post likes |
| `post_shares` | Post shares |
| `reports` | User/content reports |

## Browser Compatibility

- ✅ Chrome 70+
- ✅ Firefox 65+
- ✅ Safari 12.1+
- ✅ Edge 79+

WebRTC features (voice/video calls) require a modern browser with HTTPS.

## License

This project is provided as-is for educational and development purposes.
