Project CEI_326

Μέλη ομάδας: Νικολέτα Κακουλίδου 30427, Έλενα Σαριχανί 30752

Νικολέτα Κακουλίδου:Candidate module,API module
'Ελενα Σαριχανί:Admin module,Search module

Project structure overview:

- `Admin module`
  Includes `admin/dashboard.php` and `admin/users.php` for dashboard access and user CRUD.
- `Candidate module`
  Includes `modules/dashboard.php` and `modules/list.php` for profile entry-point and application tracking.
- `Search module`
  Includes `search/dashboard.html` as the public search landing page placeholder.
- `API module`
  Reserved for upcoming public API endpoints based on the MySQL schema in `project-root/MySQL/tables.sql`.

Database schema overview:

- `users`
  Authentication, roles, and account status.
- `candidates`
  Candidate personal profile connected one-to-one with `users`.
- `services`
  Service or education category metadata used by published lists.
- `lists`
  Published appointment lists per service and academic year.
- `list_entries`
  Ranked candidate entries inside each list.
- `applications`
  Candidate submissions and status tracking timeline.
- `activity_logs`
  Admin and user action history for reports and auditing.
