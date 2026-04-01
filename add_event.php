<?php
require "config.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST["event_date"] < date("Y-m-d")) {
        die("Nem adhatsz meg múltbeli dátumot!");
    }
    $stmt = $pdo->prepare("INSERT INTO events 
        (title, description, event_date, category, visibility, user_id)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST["title"],
        $_POST["description"],
        $_POST["event_date"],
        $_POST["category"],
        $_POST["visibility"],
        $_SESSION["user_id"]
    ]);
    header("Location: dashboard.php");
}

$selectedDate = $_GET['date'] ?? date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="hu">
<head><?php include "head.php"; ?></head>
<body>
<link rel="stylesheet" href="style.css">
<div class="top-line"></div>
<div class="orb-br"></div>

<div class="navbar">
    <a class="navbar-brand" href="dashboard.php">MSZC Gépészeti – Eseménynaptár</a>
    <div class="navbar-links">
        <span style="color:rgba(255,255,255,.45);font-size:14px;padding:8px 12px;">
            <?= htmlspecialchars($_SESSION["fullname"]) ?>
        </span>
        <a href="dashboard.php">Vissza</a>
        <a href="delete_account.php" style="color:#ff6b82;">Profil törlése</a>
        <a href="logout.php">Kijelentkezés</a>
    </div>
</div>

<div style="position:relative;z-index:1;max-width:600px;margin:40px auto;padding:0 20px;">

    <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:8px;
       color:rgba(255,255,255,.45);font-size:14px;margin-bottom:24px;transition:color .2s;"
       onmouseover="this.style.color='white'"
       onmouseout="this.style.color='rgba(255,255,255,.45)'">
        ⬅ Vissza a naptárhoz
    </a>

    <div style="
        background:rgba(255,255,255,.03);
        border:1px solid rgba(0,200,200,.18);
        border-radius:18px;
        overflow:hidden;
    ">
        <div style="height:4px;background:linear-gradient(90deg,transparent,var(--cyan),transparent);"></div>
        <div style="padding:36px 40px;">

            <h2 style="margin-bottom:6px;">Új esemény</h2>
            <p class="subtitle" style="margin-bottom:28px;">Töltsd ki az esemény adatait</p>

            <form method="post">
                <input name="title" placeholder="Esemény neve" required>

                <textarea name="description" placeholder="Leírás (opcionális)"
                    style="min-height:100px;resize:vertical;"></textarea>

                <input type="date" name="event_date"
                    value="<?= htmlspecialchars($selectedDate) ?>" required>

                <select name="category">
                    <option>Oktatás</option>
                    <option>Sport</option>
                    <option>Szórakozás</option>
                    <option>Vizsga</option>
                    <option>Egyéb</option>
                </select>

                <select name="visibility">
                    <option value="private">Privát</option>
                    <option value="class">Osztály</option>
                    <option value="school">Iskola</option>
                    <option value="public">Publikus</option>
                </select>

                <button>Mentés →</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
