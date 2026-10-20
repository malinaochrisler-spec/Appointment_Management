# Appointment Desk — PHP OOP + PDO

Appointment Desk is a local academic CRUD system based on an **Appointment** main entity. It implements the final requirements: MySQL, PDO prepared statements, OOP, add/view/update/delete, multi-field search, validation, error handling, CSRF protection, password hashing, login/logout, sessions, three database transactions, and a responsive interface.

## Project structure

| Path | Purpose |
|---|---|
| `config/config.php` | Session, database settings, escaping, CSRF, auth helpers |
| `classes/Database.php` | Reusable PDO connection with exception handling |
| `classes/Appointment.php` | Main entity class and all CRUD/search methods |
| `public/index.php` | Authenticated dashboard and appointment workflows |
| `public/transactions.php` | Read-only transaction/audit log |
| `public/login.php` | Login using `password_verify()` |
| `public/setup.php` | One-time administrator creation using `password_hash()` |
| `public/logout.php` | Session logout |
| `public/assets/style.css` | Responsive UI styling |
| `sql/database.sql` | MySQL database and tables |
| `sql/appointment_transactions.sql` | Migration for the transaction-log table |

## Database design

The `appointments` table stores the entity attributes: unique reference number, client name, email, phone, date, time, service type, status, notes, creator, and timestamps. The `users` table supports administrator/staff authentication. `appointments.created_by` is a foreign key to `users.id` with `ON DELETE SET NULL`. Indexes support searches and common status/date filtering.

`appointment_transactions` stores an audit row for every create, update, and delete. It records the appointment ID and reference number, action type, user who performed it, details, and timestamp.

## Run locally with XAMPP, WAMP, or Laragon

1. Install and start **Apache** and **MySQL**.
2. Copy this folder into your web root, for example `C:\xampp\htdocs\appointment-management-pdo`.
3. Open phpMyAdmin and import `sql/database.sql`.
4. If you are updating an existing installation, import `sql/appointment_transactions.sql` once to create the transaction-log table.
4. If your local MySQL credentials differ, edit `config/config.php`. The default XAMPP values are host `127.0.0.1`, user `root`, and an empty password.
5. Open `http://localhost/appointment-management-pdo/public/setup.php`.
6. Create an administrator account, then sign in at `http://localhost/appointment-management-pdo/public/login.php`.
7. Open the folder in VS Code and edit the PHP files as needed.

## How the system works

**Adding:** The form submits to `public/index.php`; the server validates every field, verifies the CSRF token, and passes the sanitized values to `Appointment::addAppointment()`. The method inserts data with a PDO prepared statement.

**Fetching:** `Appointment::getAppointments()` retrieves all records with a prepared search path when a keyword is supplied. The dashboard renders escaped values in a responsive table.

**Updating:** Clicking Edit loads one record by ID. The submitted form is validated and passed to `updateAppointment($id)`, which changes the record using named parameters.

**Deleting:** Delete requires a browser confirmation and a valid CSRF token. `deleteAppointment($id)` removes only the selected record by bound ID.

**Transactions (three):** Every state-changing appointment operation is atomic:

1. `addAppointment()` — starts a transaction, inserts the appointment and its `Created` audit row, then commits.
2. `updateAppointment()` — locks the row with `SELECT ... FOR UPDATE`, updates it and writes an `Updated` audit row, then commits.
3. `deleteAppointment()` — locks the row, deletes it and writes a `Deleted` audit row, then commits.

Each method rolls back on any exception and leaves the database unchanged. The **Transactions** page is only a read-only audit view of committed changes; the required transaction style is the TCL code in `classes/Appointment.php`, not the page itself.

**Sessions:** `config/config.php` starts the PHP session. Login regenerates the session ID and stores the authenticated user; protected pages call `require_auth()`; logout clears and destroys the session; CSRF tokens are stored in the session.

**Searching:** `searchAppointments($keyword)` checks the keyword across reference number, client details, service, status, and notes using `LIKE :term`; no user input is concatenated into SQL.

## Security notes

All SQL statements use prepared statements. Output is escaped with `htmlspecialchars`, passwords are never stored directly, sessions regenerate after login, destructive actions require confirmation, and all state-changing requests require CSRF validation. Database errors are logged server-side while users receive safe messages.

## Academic scope

The application intentionally focuses on the documented appointment entity and its required CRUD/search workflow. Optional enhancements such as email reminders, calendar integrations, and multi-branch scheduling are outside this submission scope.
