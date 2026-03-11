 Eseménynaptár — School Event Calendar
A PHP-based web application for managing and viewing school events on a monthly calendar. Users can register with their school and role, create events with visibility settings, and share them with classmates or the whole school.

Built for MSZC Gépészeti and affiliated schools.


✨ Features
FeatureDescription🔐 AuthenticationRegister, login, logout with session management🏫 School & RoleUsers register as Student or Teacher, linked to a school📆 Monthly CalendarNavigate months, view events by day➕ Add EventsTitle, date, category, visibility (private / class / school / public)🗑️ Delete EventsSoft-delete your own events; expired events auto-deleted🔍 Filter EventsFilter by category and visibility on the calendar🔑 Forgot PasswordSecure token-based reset link sent via email (PHPMailer)👤 Delete AccountPermanently remove account and all associated events🎨 Custom UIDark navy–gold glass morphism design

🛠️ Tech Stack
LayerTechnologyBackendPHP 8.0+DatabaseSupabase (PostgreSQL)EmailPHPMailer via Gmail SMTPStylingVanilla CSS — custom dark theme with Google FontsContainerDocker

📁 Project Structure
esemenynaptar/
├── config.php            # DB connection + session start
├── index.php             # Redirect or landing
├── dashboard.php         # Main calendar view (protected)
├── add_event.php         # Create a new event (protected)
├── delete_event.php      # Soft-delete an event (protected)
├── delete_account.php    # Permanently delete account (protected)
├── login.php             # Login form + auth logic
├── register.php          # Registration form + validation
├── logout.php            # Session destroy + redirect
├── forgot_password.php   # Request password reset email
├── reset_password.php    # Set new password via token
├── style.css             # Full UI stylesheet
├── composer.json         # PHPMailer dependency
└── Dockerfile            # Docker setup

🗄️ Database Schema
Run this SQL in your Supabase SQL Editor:
sql-- Users table
CREATE TABLE public.users (
    id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    fullname      TEXT NOT NULL,
    email         TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'student',   -- 'student' | 'teacher'
    school        TEXT NOT NULL DEFAULT 'MSZC',      -- 'MSZC' | 'SZIC' | 'KSZC'
    reset_token   TEXT DEFAULT NULL,
    reset_expires TIMESTAMPTZ DEFAULT NULL,
    created_at    TIMESTAMPTZ DEFAULT NOW()
);

-- Events table
CREATE TABLE public.events (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID REFERENCES public.users(id) ON DELETE CASCADE,
    title       TEXT NOT NULL,
    description TEXT,
    category    TEXT NOT NULL DEFAULT 'Egyéb',
    visibility  TEXT NOT NULL DEFAULT 'private',  -- 'private' | 'class' | 'school' | 'public'
    event_date  DATE NOT NULL,
    is_deleted  BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);

-- Index for faster calendar queries
CREATE INDEX idx_events_date ON public.events(event_date);
CREATE INDEX idx_events_user ON public.events(user_id);
CREATE INDEX idx_users_reset ON public.users(reset_token);

🚀 Installation
Prerequisites

PHP 8.0+
Composer
A free Supabase account

1. Clone the repository
bashgit clone https://github.com/Davedka/esemenynaptar.git
cd esemenynaptar
2. Install dependencies
bashcomposer install
3. Configure config.php
php<?php
session_start();

// ── Supabase PostgreSQL connection ──────────────────────────────
// Find these at: Dashboard → Settings → Database → Connection string → PHP
$db_host = 'aws-0-eu-central-1.pooler.supabase.com';
$db_port = '6543';
$db_name = 'postgres';
$db_user = 'postgres.YOUR_PROJECT_REF';
$db_pass = 'YOUR_SUPABASE_PASSWORD';

$pdo = new PDO(
    "pgsql:host=$db_host;port=$db_port;dbname=$db_name",
    $db_user,
    $db_pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ── Gmail SMTP ───────────────────────────────────────────────────
// Google Account → Security → 2-Step Verification → App Passwords
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your@gmail.com');
define('SMTP_PASS', 'xxxx xxxx xxxx xxxx');  // App Password (not your real password)
define('APP_URL',   'https://yoursite.com'); // No trailing slash
4. Run with Docker (optional)
bashdocker build -t esemenynaptar .
docker run -p 8080:80 esemenynaptar
Open in browser: http://localhost:8080

⚙️ How It Works
🔐 Authentication
Registration (register.php) collects full name, email, password, role (student/teacher), and school. Before inserting, it checks for duplicate emails and validates the password:
php// Password validation rules
if (strlen($password) < 9) {
    $passwordError = "Password must be at least 9 characters!";
} elseif (!preg_match('/[0-9]/', $password)) {
    $passwordError = "Password must contain at least one number!";
} elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
    $passwordError = "Password must contain at least one special character!";
}

// Passwords are stored hashed — never in plain text
password_hash($password, PASSWORD_DEFAULT)
Login (login.php) fetches the user by email and verifies the password with password_verify(). On success, it stores the user ID and name in the session:
php$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_POST["email"]]);
$user = $stmt->fetch();

if ($user && password_verify($_POST["password"], $user["password_hash"])) {
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["fullname"] = $user["fullname"];
    header("Location: dashboard.php");
}
All protected pages start with a session guard:
phpif (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

📆 Calendar (dashboard.php)
The calendar builds a monthly grid using PHP date functions. It reads the current month/year from URL params (defaulting to today), then queries events with visibility logic:
php$month = $_GET['month'] ?? date("m");
$year  = $_GET['year']  ?? date("Y");

$firstDay   = date("$year-$month-01");
$daysInMonth = date("t", strtotime($firstDay));
$startDay    = date("N", strtotime($firstDay)); // 1 = Monday
Visibility system — each event is visible based on its scope:
php// An event is shown if:
// - it belongs to you (private events)
// - it's public (everyone sees it)
// - it's school-scoped and you're in the same school

$query = "SELECT e.* FROM events e
          JOIN users u ON e.user_id = u.id
          WHERE e.is_deleted = FALSE
          AND EXTRACT(MONTH FROM e.event_date) = ?
          AND EXTRACT(YEAR FROM e.event_date) = ?
          AND (
              e.user_id = ?                              -- your own events
              OR e.visibility = 'public'                 -- visible to everyone
              OR (e.visibility = 'school' AND u.school = ?)  -- same school
          )";
Auto-expiry — past events are automatically soft-deleted when the page loads:
php$pdo->prepare("UPDATE events SET is_deleted = TRUE WHERE event_date < CURRENT_DATE")
    ->execute();
Events are then grouped by day number for easy rendering:
php$eventsByDay = [];
foreach ($events as $event) {
    $day = date("j", strtotime($event["event_date"]));
    $eventsByDay[$day][] = $event;
}
Month navigation passes updated month/year as GET params, wrapping correctly at year boundaries:
html<a href="?month=<?= $month-1 ?>&year=<?= $year ?>">← Previous</a>
<a href="?month=<?= $month+1 ?>&year=<?= $year ?>">Next →</a>

🔑 Password Reset Flow
The reset system uses a cryptographically secure token stored in the database with an expiry timestamp.
Step 1 — Request reset (forgot_password.php):
php// Generate a 64-character hex token
$token   = bin2hex(random_bytes(32));
$expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

// Store it on the user row
$pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")
    ->execute([$token, $expires, $user["id"]]);

// Build the reset link
$resetLink = "http://" . $_SERVER["HTTP_HOST"] . "/reset_password.php?token=" . $token;
Step 2 — Send email with PHPMailer via Gmail SMTP:
php$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host       = "smtp.gmail.com";
$mail->SMTPAuth   = true;
$mail->Username   = "your@gmail.com";
$mail->Password   = "app_password_here";  // App Password, not your login password
$mail->SMTPSecure = "tls";
$mail->Port       = 587;

$mail->setFrom("your@gmail.com", "MSZC Eseménynaptár");
$mail->addAddress($user["email"], $user["fullname"]);
$mail->isHTML(true);
$mail->Subject = "Password Reset";
$mail->Body    = "<a href='$resetLink'>Click here to reset your password</a>";
$mail->send();
Step 3 — Validate token (reset_password.php):
php// Token must exist AND not be expired
$stmt = $pdo->prepare(
    "SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()"
);
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("Invalid or expired link.");
}
Step 4 — Save new password and clear the token:
php$pdo->prepare(
    "UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?"
)->execute([password_hash($password, PASSWORD_DEFAULT), $user["id"]]);

🗑️ Event Deletion (delete_event.php)
Events use soft delete — they are never permanently removed from the database, only flagged. This preserves history and allows auto-expiry logic to work cleanly.
php// Verify ownership before deleting
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION["user_id"]]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: dashboard.php"); // Not your event — blocked
    exit;
}

// Soft delete: mark as deleted, don't remove the row
$pdo->prepare("UPDATE events SET is_deleted = TRUE WHERE id = ? AND user_id = ?")
    ->execute([$id, $_SESSION["user_id"]]);

👤 Account Deletion (delete_account.php)
Account deletion is permanent. Because events have ON DELETE CASCADE, deleting the user row automatically removes all their events from the database:
php// Events are removed automatically via CASCADE, but we delete explicitly too
$pdo->prepare("DELETE FROM events WHERE user_id = ?")->execute([$_SESSION["user_id"]]);
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_SESSION["user_id"]]);

// End the session
session_destroy();
header("Location: login.php");

🔒 Security Overview
ConcernHow it's handledPassword storagepassword_hash() with PASSWORD_DEFAULT (bcrypt)SQL injectionAll queries use PDO prepared statements with ? placeholdersSession hijackingSession started in config.php, user ID stored server-sideReset token32-byte random token (bin2hex(random_bytes(32))), expires in 1 hourAuthorizationEvent edit/delete checks user_id = $_SESSION["user_id"]XSSUser content rendered with htmlspecialchars()

