# Rlabz

## Setup Instructions

To get the project up and running on your local machine, follow these steps:

### 1. Prerequisites
- **PHP**: Make sure you have PHP installed (e.g., via XAMPP, WAMP, or standalone).
- **MySQL/MariaDB**: You need a running database server.

### 2. Database Configuration
1. Create a new MySQL database for the project (e.g., `rlabz_db`).
2. Import the schema to create the necessary tables. You can use the provided SQL file:
   ```bash
   mysql -u root -p rlabz_db < database/schema.sql
   ```
   *(Alternatively, use phpMyAdmin or a similar tool to import `database/schema.sql`)*
3. Update your database credentials in the configuration file located at `admin/api/db.php`.
4. To populate the database with initial data, you can run the migration script:
   ```bash
   php database/migrate.php
   ```

### 3. Running the Application
If you are using a local server like XAMPP or WAMP, place the project folder in your `htdocs` or `www` directory and access it via `http://localhost/rlabzPro/Rlabz/`.

Alternatively, you can use PHP's built-in web server. Open a terminal in the root directory and run:
```bash
php -S localhost:8000
```
Then open your browser and navigate to `http://localhost:8000`.

### Folder Structure Overview
- `admin/` - Admin dashboard and API endpoints
- `assets/` - Static assets (CSS, JS, images)
- `database/` - Database schemas and migration scripts
- `dev/` - Development commands, JSON dumps, and reports
- `uploads/` - Uploaded images and media
