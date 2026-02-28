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
?>

<link rel="stylesheet" href="style.css">

<div class="container">
    <h2>Új esemény</h2>

    <form method="post">
        <input name="title" placeholder="Esemény neve" required>
        <textarea name="description" placeholder="Leírás"></textarea>
        <input type="date" name="event_date" required>

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

        <button>Mentés</button>
    </form>
</div>