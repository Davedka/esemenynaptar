# Eseménynaptár — School Event Calendar

A PHP-based web application for managing and viewing school events on a monthly calendar. Users can register with their school and role, create events with visibility settings, and share them with classmates or the whole school.

Built for MSZC Gépészeti and affiliated schools.

The app is written in PHP 8.0+ with a Supabase (PostgreSQL) database in the background, PHPMailer for email, and a fully custom dark navy–gold CSS theme. Everything can be run with Docker so no local server setup is needed.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.0+ |
| Database | Supabase (PostgreSQL) |
| Email | PHPMailer via Gmail SMTP |
| Styling | Vanilla CSS — custom dark theme with Google Fonts |
| Container | Docker |

## Project Structure

```
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
```

## Database Schema

Run this SQL in your Supabase SQL Editor:

```sql
-- Users table
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

-- Indexes for faster calendar queries
CREATE INDEX idx_events_date ON public.events(event_date);
CREATE INDEX idx_events_user ON public.events(user_id);
CREATE INDEX idx_users_reset ON public.users(reset_token);
```

## Installation

**Prerequisites:** PHP 8.0+, Composer, a free Supabase account.

```bash
git clone https://github.com/Davedka/esemenynaptar.git
cd esemenynaptar
composer install
```

Then fill in `config.php` with your Supabase connection string and Gmail App Password:

```php
<?php
session_start();

// Supabase PostgreSQL — Dashboard → Settings → Database → Connection string → PHP
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

// Gmail SMTP — Google Account → Security → 2-Step Verification → App Passwords
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your@gmail.com');
define('SMTP_PASS', 'xxxx xxxx xxxx xxxx'); // App Password, not your real password
define('APP_URL',   'https://yoursite.com'); // No trailing slash
```

To run with Docker instead of a local server:

```bash
docker build -t esemenynaptar .
docker run -p 8080:80 esemenynaptar
```

Then open `http://localhost:8080` in your browser.

## How It Works

### Authentication

Registration collects full name, email, password, role (student/teacher), and school. The password goes through validation rules before the account is created:

```php
// register.php
if (strlen($password) < 9) {
    $passwordError = "Password must be at least 9 characters!";
} elseif (!preg_match('/[0-9]/', $password)) {
    $passwordError = "Password must contain at least one number!";
} elseif (!preg_match('/[!@#$%^&*()\-_=+\[\]{};:",.<>?\/]/', $password)) {
    $passwordError = "Password must contain at least one special character!";
}

// Passwords are stored hashed — never in plain text
$hash = password_hash($password, PASSWORD_DEFAULT);
```

On login the password is verified against the stored hash and the user ID goes into the session:

```php
// login.php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_POST["email"]]);
$user = $stmt->fetch();

if ($user && password_verify($_POST["password"], $user["password_hash"])) {
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["fullname"] = $user["fullname"];
    header("Location: dashboard.php");
}
```

Every protected page has a session guard at the top so unauthenticated visitors are redirected immediately:

```php
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
```

### Calendar (dashboard.php)

The calendar builds a monthly grid using PHP date functions. It reads the month and year from URL params (defaulting to today) and queries events with visibility logic so each user only sees what they're supposed to:

```php
$month = $_GET['month'] ?? date("m");
$year  = $_GET['year']  ?? date("Y");

$firstDay    = date("$year-$month-01");
$daysInMonth = date("t", strtotime($firstDay));
$startDay    = date("N", strtotime($firstDay)); // 1 = Monday

// Show: your own events, public events, and school-scoped events from your school
$query = "SELECT e.* FROM events e
          JOIN users u ON e.user_id = u.id
          WHERE e.is_deleted = FALSE
          AND EXTRACT(MONTH FROM e.event_date) = ?
          AND EXTRACT(YEAR  FROM e.event_date) = ?
          AND (
              e.user_id = ?
              OR e.visibility = 'public'
              OR (e.visibility = 'school' AND u.school = ?)
          )";
```

Past events get automatically soft-deleted every time the dashboard loads:

```php
$pdo->prepare("UPDATE events SET is_deleted = TRUE WHERE event_date < CURRENT_DATE")
    ->execute();
```

Events are then grouped by day number so rendering the grid is straightforward:

```php
$eventsByDay = [];
foreach ($events as $event) {
    $day = date("j", strtotime($event["event_date"]));
    $eventsByDay[$day][] = $event;
}
```

Month navigation passes updated GET params. The links wrap correctly at year boundaries (PHP handles the month overflow automatically when building the next URL):

```php
// Previous month
$prevMonth = $month == 1 ? 12 : $month - 1;
$prevYear  = $month == 1 ? $year - 1 : $year;

// Next month
$nextMonth = $month == 12 ? 1 : $month + 1;
$nextYear  = $month == 12 ? $year + 1 : $year;
```

```html
<a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>">← Previous</a>
<a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>">Next →</a>
```

### Password Reset Flow

The reset system uses a cryptographically secure token stored in the database with a 1-hour expiry.

Step 1 — generate and store the token, then send the reset link via PHPMailer over Gmail SMTP:

```php
// forgot_password.php
$token   = bin2hex(random_bytes(32)); // 64-character hex token
$expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

$pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")
    ->execute([$token, $expires, $user["id"]]);

$resetLink = "http://" . $_SERVER["HTTP_HOST"] . "/reset_password.php?token=" . $token;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host       = "smtp.gmail.com";
$mail->SMTPAuth   = true;
$mail->Username   = SMTP_USER;
$mail->Password   = SMTP_PASS;
$mail->SMTPSecure = "tls";
$mail->Port       = 587;
$mail->setFrom(SMTP_USER, "MSZC Eseménynaptár");
$mail->addAddress($user["email"], $user["fullname"]);
$mail->isHTML(true);
$mail->Subject = "Password Reset";
$mail->Body    = "<a href='{$resetLink}'>Click here to reset your password</a>";
$mail->send();
```

Step 2 — validate the token and expiry, then save the new password and clear the token:

```php
// reset_password.php
$stmt = $pdo->prepare(
    "SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()"
);
$stmt->execute([$_GET["token"]]);
$user = $stmt->fetch();

if (!$user) {
    die("Invalid or expired link.");
}

$pdo->prepare(
    "UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?"
)->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user["id"]]);
```

### Event Deletion (delete_event.php)

Events use soft delete — they are flagged as deleted rather than permanently removed. The ownership check makes sure one user cannot delete another user's events:

```php
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION["user_id"]]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: dashboard.php"); // Not your event — blocked
    exit;
}

$pdo->prepare("UPDATE events SET is_deleted = TRUE WHERE id = ? AND user_id = ?")
    ->execute([$id, $_SESSION["user_id"]]);
```

### Account Deletion (delete_account.php)

Account deletion is permanent. Because events have `ON DELETE CASCADE` in the schema, deleting the user row automatically removes all their events too:

```php
$pdo->prepare("DELETE FROM events WHERE user_id = ?")->execute([$_SESSION["user_id"]]);
$pdo->prepare("DELETE FROM users  WHERE id = ?")     ->execute([$_SESSION["user_id"]]);

session_destroy();
header("Location: login.php");
```

## Design

The UI is a custom dark navy–gold glass morphism theme written entirely in vanilla CSS with no frameworks. The palette is defined as CSS variables so every component stays consistent:

```css
:root {
    --navy:       #0b2e59;
    --navy-dark:  #071d3a;
    --navy-light: #1a4a8a;
    --gold:       #c8972a;
    --gold-light: #f0c76b;
    --bg:         #060f1e;
    --surface:    rgba(255,255,255,.04);
    --border:     rgba(255,255,255,.10);
}
```

The background is built from layered radial mesh gradients on top of a deep `#060f1e` base, plus a subtle CSS grid texture rendered with `body::before`. Two floating orbs animate with a gentle `@keyframes floatOrb` loop — one gold in the top-left, one blue in the bottom-right — giving the page depth without any JavaScript.

Cards, the calendar, and the navbar all use `backdrop-filter: blur()` for the frosted glass effect. The navbar sticks to the top with a blurred background so it stays readable over the animated layers behind it:

```css
.navbar {
    position: sticky;
    top: 0;
    background: rgba(6,15,30,.7);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255,255,255,.07);
}
```

Form inputs glow gold on focus. Auth forms slide up on load via a short `slideUp` animation. Today's date on the calendar gets a red circular badge. The whole color system — navy, gold, and red — maps directly to the school's identity colors.

## Security Overview

| Concern | How it's handled |
|---|---|
| Password storage | `password_hash()` with `PASSWORD_DEFAULT` (bcrypt) |
| SQL injection | All queries use PDO prepared statements with `?` placeholders |
| Session hijacking | Session started in `config.php`, user ID stored server-side |
| Reset token | 32-byte random token (`bin2hex(random_bytes(32))`), expires in 1 hour |
| Authorization | Event edit/delete checks `user_id = $_SESSION["user_id"]` |
| XSS | User content rendered with `htmlspecialchars()` |
