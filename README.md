## 🏛️ Architecture: MVC Flow

```
HTTP Request
     │
     ▼
[ Bramus Router ] ─── (config/routes.php)
     │
     ▼
[ Controller ] ────── (app/Controllers/ForumController.php)
     │  ├── Validates session, CSRF, input
     │  ├── Interacts with Models (User, Discussion, Comment)
     │  └── Traverses & flattens tree for display
     ▼
[ Model / PDO ] ───── (app/Models/*.php)
     │  ├── Database connection (app/Models/Database.php)
     │  └── Executes prepared SQL statements against MySQL
     ▼
[ View Engine ] ───── (Mustache Templates: app/Views/*.mustache)
     │  └── Escapes untrusted text and renders responsive HTML
     ▼
HTTP Response
```

---

## 📁 Project Structure

```
forum/
├── app/
│   ├── Controllers/
│   │   └── ForumController.php    # Request dispatching, auth, & tree rendering
│   ├── Models/
│   │   ├── Database.php           # Singleton PDO connection wrapper
│   │   ├── Discussion.php         # Discussion query methods
│   │   ├── Comment.php            # Nested comment model, tree builder & validator
│   │   └── User.php               # User registration, lookup, & password hashing
│   ├── Views/
│   │   ├── partials/
│   │   │   └── nav.mustache       # Navigation header & auth state
│   │   ├── discussion.mustache    # Discussion thread view & reply form
│   │   ├── home.mustache          # Discussion listing
│   │   ├── login.mustache         # Sign-in form
│   │   └── register.mustache      # Registration form
│   └── Security.php               # CSRF, session, and XSS sanitization helpers
├── config/
│   ├── database.php               # DB host, credentials, & DSN config
│   └── routes.php                 # Bramus routing declarations
├── database/
│   └── schema.sql                 # Table definitions & fictional seed dataset
├── public/
│   ├── css/
│   │   └── style.css              # Minimal clean forum stylesheet
│   ├── .htaccess                  # Front controller routing rules
│   └── index.php                  # Web server entry point & session setup
├── storage/
│   └── logs/                      # Application error logs
├── .gitignore                     # Git ignore rules (vendor, logs, cache)
├── .htaccess                      # Root rewrite rules
├── composer.json                  # Composer package declarations
├── composer.lock                  # Locked package versions
└── README.md                      # Documentation & setup guide
```

---

## 🗄️ Database Schema & Seed Data

### Schema Summary
1. **`users`**: `id` (PK), `username`, `email`, `password_hash`, `avatar_settings`, `created_at`.
2. **`discussions`**: `id` (PK), `user_id` (FK), `title`, `body`, `created_at`.
3. **`comments`**: `id` (PK), `user_id` (FK), `discussion_id` (FK), `parent_id` (nullable FK), `body`, `created_at`. Includes composite foreign key `(parent_id, discussion_id) REFERENCES comments(id, discussion_id)`.

### Seed Tree (Discussion #1: "How do I learn PHP?")
- **Alice** (depth 0, parent: NULL): *"Start with the PHP manual."*
  - **Ben** (depth 1, parent: 1): *"Try the examples as you read."*
    - **Cara** (depth 2, parent: 2): *"Build a small project too."*
      - **Ben** (depth 3, parent: 3): *"Start with a forum."*
        - **Eli** (depth 4, parent: 6): *"Add replies next."*
  - **Dee** (depth 1, parent: 1): *"Which manual section first?"*
- **Eli** (depth 0, parent: NULL): *"Practice SQL as well."*

---

## 🚀 Setup & Installation

### 1. Requirements
- PHP 8.2 or newer with `pdo_mysql` enabled
- MySQL 8.0 or MariaDB 10.4+
- Apache Web Server (with `mod_rewrite` enabled)
- Composer

### 2. Database Setup
Import the schema into your local MySQL instance:
```bash
mysql -u root -p < database/schema.sql
```

### 3. Install Dependencies
```bash
composer install --no-dev
```

### 4. Configure Virtual Host / Apache
Point your web root to `public/` or set up a junction in your XAMPP `htdocs`:
```powershell
cmd /c mklink /J "C:\xampp\htdocs\forum" "path\to\forum"
```

Access the forum at:
```
http://localhost/forum/
```

---

## 🧪 Verification & Test Steps

1. **Viewing Nested Thread:** Visit `/forum/discussions/1` to inspect pre-seeded replies with visual indentation and depth badges (0 through 4).
2. **Register & Login:** Register a new user at `/forum/register` and sign in at `/forum/login`.
3. **Post Depth-5 Reply:** Reply to comment #7 to produce a reply at depth 5 (`140px` indentation).
4. **Post Top-Level Reply:** Submit a reply with parent set to `— Top-level comment —` to verify depth 0 placement.
5. **Guest Write Rejection:** Log out and attempt a POST to `/forum/discussions/1/comments`; verify response is rejected (`401 Unauthorized`).
6. **XSS Escaping Test:** Post `<script>alert(1)</script>`; verify it is safely HTML-escaped and rendered literally without script execution.
7. **CSRF Validation:** Verify tampering with or omitting `csrf_token` yields `403 Forbidden`.



