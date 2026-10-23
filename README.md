# RLabz — Next Gen Innovation Hub

RLabz is an advanced project repository and showcase platform featuring interactive 3D robot elements, project timeline visualization, comprehensive project detail views, and a full-featured admin management panel with SSL certificate monitoring and alert notifications.

---

## Quick Start & Running the Project

### 1. Prerequisites
- **PHP 8.0+** with PDO MySQL & OpenSSL extensions enabled.
- **MySQL / MariaDB** (via XAMPP, WAMP, or standalone service).

### 2. Database Configuration
1. Ensure your MySQL server is running (e.g., in XAMPP start MySQL).
2. Configure credentials in [admin/api/db.php](file:///c:/Users/annjo/Desktop/rajagiri/Projects/rlabzPro/Rlabz/admin/api/db.php):
   ```php
   $host = 'localhost';
   $dbname = 'rlabz_db';
   $username = 'root';
   $password = '';
   ```

### 3. Migration & Seeder Commands

The project includes idempotent migration and seeder tools:

#### A. Safe Migration & Seeder (Recommended)
Upgrades existing tables, adds any missing columns, and seeds any missing projects from `dev/projects_dump.json` **without overwriting** existing edits:
```bash
php database/migrate.php
```

#### B. Fresh Database Rebuild (Wipe & Re-seed)
Drops existing tables and performs a clean installation with the 17 verified projects:
```bash
php database/migrate.php --fresh
```

#### C. Seed Project Milestones (Non-destructive)
Seeds project timeline milestones (requirements, architecture, deployment, etc.):
```bash
php dev/seed_milestones.php
```

#### D. Export Database State to Seeder Dump
Exports the latest database projects, team members, faculty, screenshots, and metadata into `dev/projects_dump.json`:
```bash
php dev/export_projects_json.php
```

---

## 4. Launching the Local Server

Run PHP's built-in web server in the project root directory:
```bash
php -S localhost:8000
```

Open your browser and navigate to:
- **Public Website**: `http://localhost:8000/index.html`
- **Project Repository**: `http://localhost:8000/public/projects.html`
- **Admin Dashboard**: `http://localhost:8000/admin/`

---

## 5. Admin Access & Credentials

- **URL**: `http://localhost:8000/admin/pages/overview.php` (or `/admin/login.php`)
- **Default Username**: `admin`
- **Default Password**: `admin123`

### Key Admin Features:
1. **Overview Dashboard**: Real-time project statistics, status breakdowns, and compact SSL Expiry & Announcements widget.
2. **Manage Projects**: Full project management with dynamic filtering by **Category**, **Department**, and **Status**, live search, and pagination.
3. **Add / Edit Project**: Reorganized forms with logical sections (Basic Info, Classification, Media & Links, Team & Faculty) and conditional fields.
4. **SSL Certificates Tracker**: Live domain certificate monitoring, expiry tracking, search buttons, status pills (Valid, Expiring Soon, Expired, Not Able to Fetch), and pagination.
5. **Announcements & Alerts**: Standalone notification center with instant unread filtering, bulk "Mark All Read", and distinct color highlights for SSL expiry.

---

## 6. Official Project Data (17 Projects)

The repository is synchronized with the 16 projects from the official documentation plus Rajagiri Flora:
1. Sahrdaya Counselling
2. RLabZ Website Revamp
3. International Conference Website
4. Splendore Website
5. Mentoring App
6. Facility Booking App
7. Blood Donation App
8. Placement Analytics
9. Euphoria Website
10. Arkon Medical Systems
11. DYUTI Website Content Management
12. Sparc Mobile App
13. STUBA Platform
14. Campus Connect
15. ColabSphere
16. Project of Exam Cell
17. Rajagiri Flora

---

## 7. Directory Structure

```
Rlabz/
├── admin/               # Admin panel, pages, and API endpoints
│   ├── api/             # Backend AJAX actions (SSL cron, mark read, projects)
│   ├── includes/        # Shared header, sidebar navigation, and footer
│   └── pages/           # Overview, Manage Projects, SSL, Announcements, Users
├── assets/              # Static styling, Three.js 3D robot, and client scripts
│   ├── css/             # styles.css
│   ├── images/          # rlabz-icon.png, logos, and project media
│   └── js/              # robot.js, script.js, projects-page.js
├── database/            # Database schema & migration engine
│   ├── migrate.php      # Migration & safe seeder engine
│   └── schema.sql       # Initial table definitions
├── dev/                 # Developer tools & data dumps
│   ├── export_projects_json.php # Export DB state to JSON
│   ├── projects_dump.json       # Master seeder data (17 verified projects)
│   └── seed_milestones.php      # Milestones seeder
├── public/              # Public facing pages
│   ├── projects.html            # Public project repository with filters
│   └── project-details.html     # Comprehensive project detail view
└── index.html           # Main landing page with 3D robot & interactive timeline
```
