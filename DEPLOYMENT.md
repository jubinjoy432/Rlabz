# RLabz — Production Deployment & Handover Guide
**Target Audience:** System Administrators, IT Infrastructure Team, and Hosting Authorities  
**System Name:** RLabz (Next-Gen Innovation Hub & Project Repository)  
**Architecture:** Lightweight 3-Tier LAMP/LEMP (HTML5/CSS3/Vanilla JS + PHP 8.0+ + MySQL/MariaDB)

---

## 1. Server Prerequisites

| Component | Minimum Requirement | Recommended |
| :--- | :--- | :--- |
| **Operating System** | Linux (Ubuntu, Debian, AlmaLinux, RockyLinux, or cPanel/CentOS) | Linux x86_64 |
| **Web Server** | Apache 2.4+ (with `mod_rewrite`, `mod_headers`) or Nginx 1.20+ | Apache 2.4+ |
| **PHP Runtime** | PHP 8.0+ | PHP 8.1 or 8.2 |
| **PHP Extensions** | `pdo`, `pdo_mysql`, `openssl`, `mbstring`, `fileinfo`, `json` | Enabled in `php.ini` |
| **Database** | MySQL 5.7+ / 8.0+ or MariaDB 10.3+ | Default charset: `utf8mb4` |
| **Storage / RAM** | 1 Core vCPU, 1 GB RAM, 2 GB SSD Storage | 2 GB RAM for high concurrency |

---

## 2. Five-Minute Deployment Procedure

### Step 1: Upload Files
Upload all project files to your web server root directory (`public_html` on cPanel or `/var/www/html/` on a Linux VPS).

### Step 2: Configure Database Credentials
Create or edit the `.env` file in the project root:
```ini
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
```

### Step 3: Set Storage Permissions
Ensure the web server user has write permissions to the `/uploads/` directory:
```bash
chmod 755 uploads/
# On Ubuntu/Debian Apache:
chown -R www-data:www-data uploads/
```

### Step 4: Run Pre-Flight Diagnostics
Open your browser and visit:
```text
https://your-domain.com/health.php
```
Verify that all runtime environment checks, extensions, and directory permissions show **"✓ Pass"**.

### Step 5: Initialize the Database (Automatic or Visual)
Choose one of the following methods:
* **Option A (One-Click Web Setup):** Visit `https://your-domain.com/setup.php` in your browser. All 8 tables and default admin credentials will be created automatically.
* **Option B (Zero-Touch Auto-Run):** Simply open `https://your-domain.com/index.html`. The system's built-in auto-bootstrap mechanism in `admin/api/db.php` will detect the empty database and run `database/schema.sql` automatically.
* **Option C (Terminal CLI):** Run:
  ```bash
  php setup.php
  ```

---

## 3. Initial Administrator Access

* **Portal URL:** `https://your-domain.com/admin/login.php`
* **Default Username:** `admin`
* **Default Password:** `admin123`

> [!IMPORTANT]
> Immediately upon your first login, navigate to **Manage Users** (`/admin/pages/users.php`) and update the administrator password.

---

## 4. Background SSL Certificate Monitoring (Cron Setup)

RLabz includes an automated SSL health checker that scans student/project deployment domains on port 443 and alerts administrators before certificates expire.

Add the following daily cron task to your server crontab (e.g., runs once daily at 02:00 AM):

```bash
# Linux Server Crontab (crontab -e):
0 2 * * * /usr/bin/php /var/www/html/admin/api/cron_ssl_reminder.php > /dev/null 2>&1

# cPanel Cron Job Command:
php -q /home/username/public_html/admin/api/cron_ssl_reminder.php
```

---

## 5. Security & Protection Features Built-in

* **Shielded Credentials:** The root `.htaccess` completely blocks public web requests to `.env`, `.git`, `database/`, and `dev/`.
* **Script Execution Prevention:** The `uploads/.htaccess` blocks execution of uploaded `.php`, `.cgi`, or `.sh` files.
* **SQL Injection Immunity:** All database operations utilize PDO prepared statements with parameterized inputs.
* **Cryptographic Passwords:** User authentication uses PHP native `password_hash()` (Bcrypt).
* **Zero SaaS Dependencies:** The SSL checker connects via native OpenSSL socket streams; no external paid APIs required.

---

## 6. Daily Automated Backup Script

Save the following as `/etc/cron.daily/rlabz-backup` on Linux:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/rlabz"
DATE=$(date +"%Y%m%d")
mkdir -p $BACKUP_DIR

# Backup MySQL Database
mysqldump -u your_user -p'your_pass' rlabz_db | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup User Uploads
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" -C /var/www/html uploads

# Keep last 14 days of backups
find $BACKUP_DIR -type f -mtime +14 -delete
```

