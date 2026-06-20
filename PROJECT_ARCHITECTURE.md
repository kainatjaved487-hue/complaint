# Complaint Management System - Project Architecture & Memory

This document serves as the primary reference for the project's skeleton and core logic. Use this as a source of truth when implementing new modules to ensure consistency and prevent architectural deviation.

## 1. Project Overview
A robust **Complaint Management System** built with a modular PHP architecture and a dynamic Role-Based Access Control (RBAC) system.

**Base Path:** `c:/xampp/htdocs/complaint/`

## 2. Technical Stack
- **Backend:** PHP 8.x (PDO for DB interactions)
- **Database:** MySQL (UTF8MB4)
- **Frontend:** AdminLTE 4 (Beta), Bootstrap 5, Bootstrap Icons
- **Auth:** Multi-identifier login (Email, CNIC, or Registration Number)
- **Security:** Dynamic ACL (Access Control List) gating via database

## 3. Directory Breakdown
- `/assets`: Contains core CSS/JS/Images. 
- `/core`: Backend engine.
  - `auth.php`: Class-based authentication & registration logic.
  - `db.php`: Singleton-style PDO connection mapping.
  - `config.php`: Global constants (DB credentials, BASE_URL).
  - `session.php`: Security middleware (login checks, role helpers).
- `/dashboards`: Role-specific functionalities.
  - `super_admin/`: System management (Users, Roles, Pages).
- `/includes`: UI Layout fragments.
  - `header.php`: The "Gatekeeper". Performs security checks, sets breadcrumbs, and initializes settings.
  - `sidebar.php`: Recursive menu builder fetching from `role_access`.
  - `footer.php`: Closing tags and common scripts.
- `/uploads`: Destination for user-uploaded files.

## 4. Database Schema Reference (Core Tables)
- **`users`**: `id`, `name`, `email`, `password`, `role`, `identity_no`, `registration_no`, `is_active`, `avatar`.
- **`sys_roles`**: `role_key` (PK), `role_name`, `is_system_role`.
- **`sys_pages`**: `id`, `parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`.
- **`role_access`**: `role_key`, `page_id`. (Links roles to pages).
- **`system_settings`**: `setting_key`, `setting_value`.

## 5. Key Logic Patterns

### A. The Gatekeeper Pattern (`header.php`)
Every protected page includes `header.php`. It matches the current `SCRIPT_NAME` against `sys_pages`. If the page is restricted and the user's role isn't linked in `role_access`, it terminates execution (`die`).

### B. Recursive Sidebar (`sidebar.php`)
The `buildMenu()` function in `sidebar.php` recursively fetches pages from `sys_pages` where the `parent_id` matches and the user has access. This ensures a nested, permission-aware navigation.

### C. Multi-ID Auth (`auth.php`)
Login accepts one input (`identifier`) and checks it against `email`, `identity_no`, and `registration_no` simultaneously.

### D. Theme Persistence
Uses a JavaScript snippet in `header.php` to immediately apply `data-bs-theme` from `localStorage` before any content renders, preventing "white flash".

## 6. Development Rules
1. **Always use PDO**: No raw `mysqli` calls.
2. **Pathing**: Use `BASE_URL` for frontend assets and `__DIR__` or `APP_ROOT` for backend includes.
3. **Security**: New pages **must** be added to `sys_pages` and granted via `role_access` to be visible/accessible.
4. **Consistency**: Use AdminLTE's "card" and "small-box" patterns for UI elements.
