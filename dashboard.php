<?php
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/* Automatikus törlés */
$pdo->prepare("UPDATE events 
               SET is_deleted = TRUE 
               WHERE event_date < CURDATE()")
    ->execute();

/* Aktuális hónap */
$month = $_GET['month'] ?? date("m");
$year = $_GET['year'] ?? date("Y");

$firstDayOfMonth = date("$year-$month-01");
$daysInMonth = date("t", strtotime($firstDayOfMonth));
$startDay = date("N", strtotime($firstDayOfMonth)); // 1 = hétfő

/* Szűrés */
$categoryFilter = $_GET['category'] ?? '';
$visibilityFilter = $_GET['visibility'] ?? '';

$query = "SELECT * FROM events 
          WHERE user_id = ? 
          AND is_deleted = FALSE
          AND MONTH(event_date) = ?
          AND YEAR(event_date) = ?";

$params = [$_SESSION["user_id"], $month, $year];

if ($categoryFilter) {
    $query .= " AND category = ?";
    $params[] = $categoryFilter;
}

if ($visibilityFilter) {
    $query .= " AND visibility = ?";
    $params[] = $visibilityFilter;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

/* Események csoportosítása nap szerint */
$eventsByDay = [];
foreach ($events as $event) {
    $day = date("j", strtotime($event["event_date"]));
    $eventsByDay[$day][] = $event;
}
?>

<link rel="stylesheet" href="style.css">

<style>
.calendar {
    width: 95%;
    max-width: 1000px;
    margin: 30px auto;
    background: white;
    padding: 20px;
    border-radius: 15px;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
}

.day-name {
    font-weight: bold;
    text-align: center;
}

.day {
    min-height: 100px;
    border: 1px solid #ddd;
    padding: 5px;
    border-radius: 8px;
    font-size: 14px;
    position: relative;
}

.today {
    background: #d1f0ff;
}

.event {
    background: #2a5298;
    color: white;
    padding: 3px;
    margin-top: 3px;
    border-radius: 5px;
    font-size: 12px;
}
</style>

<div class="navbar">
    <div>
        <strong>MSZC Gépészeti – Eseménynaptár</strong>
    </div>
    <div>
        <?= $_SESSION["username"] ?> |
        <a href="add_event.php">Új esemény</a>
        <a href="logout.php">Kijelentkezés</a>
    </div>
</div>

<div class="calendar">

<h2><?= $year ?> - <?= $month ?></h2>

<a href="?month=<?= $month-1 ?>&year=<?= $year ?>">⬅ Előző</a>
<a href="?month=<?= $month+1 ?>&year=<?= $year ?>">Következő ➡</a>

<hr>

<form method="get">
    <input type="hidden" name="month" value="<?= $month ?>">
    <input type="hidden" name="year" value="<?= $year ?>">

    <select name="category">
        <option value="">Összes kategória</option>
        <option>Oktatás</option>
        <option>Sport</option>
        <option>Szórakozás</option>
        <option>Vizsga</option>
        <option>Egyéb</option>
    </select>

    <select name="visibility">
        <option value="">Összes láthatóság</option>
        <option value="private">Privát</option>
        <option value="class">Osztály</option>
        <option value="school">Iskola</option>
        <option value="public">Publikus</option>
    </select>

    <button>Szűrés</button>
</form>

<hr>

<div class="calendar-grid">

<?php
$days = ["H", "K", "Sze", "Cs", "P", "Szo", "V"];
foreach ($days as $d) {
    echo "<div class='day-name'>$d</div>";
}

/* Üres cellák hónap elején */
for ($i = 1; $i < $startDay; $i++) {
    echo "<div></div>";
}

/* Napok kirajzolása */
for ($day = 1; $day <= $daysInMonth; $day++) {

    $isToday = ($day == date("j") && $month == date("m") && $year == date("Y")) 
                ? "today" : "";

    echo "<div class='day $isToday'>";
    echo "<strong>$day</strong>";

    if (isset($eventsByDay[$day])) {
        foreach ($eventsByDay[$day] as $event) {
            echo "<div class='event'>";
            echo htmlspecialchars($event["title"]);

            if ($event["event_date"] == date("Y-m-d")) {
                echo " (Folyamatban)";
            }

            echo "</div>";
        }
    }

    echo "</div>";
}
?>

</div>
</div>