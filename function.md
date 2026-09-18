# RLabz System Functionality Documentation

## 1. Core Purpose
The RLabz system functions as a dynamic digital portfolio, project repository, and content management system. It is designed to showcase projects developed by students/teams, track their development milestones, monitor their deployment status (including SSL health), and provide a public-facing gallery for clients and recruiters.

## 2. Public-Facing Functionality (Frontend)
The public interface is aimed at visitors, clients, and students.
- **Landing Page (`index.html`)**: Introduces RLabz, lists core solutions via an interactive bento grid, and provides a contact form for clients.
- **Project Gallery (`projects.html`)**: 
  - Displays a grid of all registered projects.
  - Features real-time filtering (by category, technology, status) and search.
  - Project cards display thumbnail, title, short description, year, status badge, and tech stack tags.
- **Project Details (`project-details.html`)**: 
  - A deep-dive page into a specific project.
  - Displays full descriptions, objectives, problem statements, and expected outcomes.
  - Shows an image gallery/carousel of project screenshots.
  - Lists the development team (with avatars, roles, LinkedIn links).
  - Lists guiding faculty members.
  - Provides external links (GitHub repo, Live Demo, Poster).

## 3. Administrative Functionality (Backend/Dashboard)
The `/admin` portal (secured by login) allows authorized administrators to manage the platform's content.

### Project Management
- **CRUD Operations**: Admins can Create, Read, Update, and Delete projects.
- **Project Metadata**: Manage title, department (e.g., MCA), batch year, category (e.g., Web App), duration, and current status (In Development, Deployed, Completed).
- **Rich Content**: Edit full text descriptions, objectives, and key features.
- **Media Management**: Upload and manage project thumbnails, main images, and multiple gallery screenshots.

### Team & Faculty Management
- **Student Teams**: Add/remove team members to a project, including names, register numbers, roles, photos, and social links.
- **Faculty Mentors**: Add/remove faculty members associated with projects, tracking their designation and contact info.

### Project Health & Tracking
- **Milestone Tracking**: Create and manage a timeline for projects. Track milestones by date and status (Upcoming, Current, Completed).
- **SSL Certificate Monitoring**: A dedicated module to track the SSL certificates of deployed project domains. Records provider, issue date, expiry date, and status alerts for renewals.

### System Administration
- **Announcements System**: Admins can generate and view system alerts and notifications (e.g., SSL expiry warnings).
- **Authentication**: Secure admin login utilizing PHP `password_hash` for credential security.

## 4. Backend & Database Architecture
The backend is powered by PHP APIs that communicate with a MySQL database.
- `admin_users`: Manages administrator credentials.
- `projects`: The core table holding all project metadata and descriptions.
- `project_members` & `project_faculty`: Relational tables linking people to projects.
- `project_screenshots`: Relational table storing additional gallery images for projects.
- `project_ssl_certs`: Tracks the SSL health of live demo links.
- `project_milestones`: Stores timeline events for projects.
- `admin_announcements`: Stores system notifications and alerts.
