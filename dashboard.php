<?php
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/* Automatikus törlés */
$pdo->prepare("UPDATE events 
               SET is_deleted = TRUE 
               WHERE event_date < CURRENT_DATE")
    ->execute();

/* Aktuális hónap */
$month = $_GET['month'] ?? date("m");
$year = $_GET['year'] ?? date("Y");

$firstDayOfMonth = date("$year-$month-01");
$daysInMonth = date("t", strtotime($firstDayOfMonth));
$startDay = date("N", strtotime($firstDayOfMonth));

/* Szűrés */
$categoryFilter = $_GET['category'] ?? '';
$visibilityFilter = $_GET['visibility'] ?? '';

/* Bejelentkezett user iskolájának lekérése */
$userStmt = $pdo->prepare("SELECT school FROM users WHERE id = ?");
$userStmt->execute([$_SESSION["user_id"]]);
$currentUser = $userStmt->fetch();

$query = "SELECT e.* FROM events e
          JOIN users u ON e.user_id = u.id
          WHERE e.is_deleted = FALSE
          AND EXTRACT(MONTH FROM e.event_date) = ?
          AND EXTRACT(YEAR FROM e.event_date) = ?
          AND (
              e.user_id = ?
              OR e.visibility = 'public'
              OR (e.visibility = 'school' AND u.school = ?)
          )";

$params = [$month, $year, $_SESSION["user_id"], $currentUser["school"]];

if ($categoryFilter) {
    $query .= " AND e.category = ?";
    $params[] = $categoryFilter;
}

if ($visibilityFilter) {
    $query .= " AND e.visibility = ?";
    $params[] = $visibilityFilter;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

$eventsByDay = [];
foreach ($events as $event) {
    $day = date("j", strtotime($event["event_date"]));
    $eventsByDay[$day][] = $event;
}

$monthNames = [
    1=>"Január", 2=>"Február", 3=>"Március", 4=>"Április",
    5=>"Május", 6=>"Június", 7=>"Július", 8=>"Augusztus",
    9=>"Szeptember", 10=>"Október", 11=>"November", 12=>"December"
];
?>

<link rel="stylesheet" href="style.css">

<div class="top-line"></div>
<div class="orb-br"></div>

<div class="navbar">
    <a class="navbar-brand" href="dashboard.php">MSZC Gépészeti – Eseménynaptár</a>
    <div class="navbar-links">
        <span style="color:rgba(255,255,255,.45);font-size:14px;padding:8px 12px;">
            <?= htmlspecialchars($_SESSION["fullname"]) ?>
        </span>
        <a href="add_event.php">Új esemény</a>
        <a href="delete_account.php" style="color:#ff6b82;">Profil törlése</a>
        <a href="logout.php">Kijelentkezés</a>
    </div>
</div>

<div class="calendar">

    <div class="calendar-header">
        <div>
            <div class="page-title"><?= $monthNames[(int)$month] ?> <?= $year ?></div>
            <div class="page-subtitle">Eseménynaptár</div>
        </div>
        <div class="calendar-nav">
            <a href="?month=<?= $month-1 <= 0 ? 12 : $month-1 ?>&year=<?= $month-1 <= 0 ? $year-1 : $year ?>">
                <button>⬅ Előző</button>
            </a>
            <a href="?month=<?= $month+1 > 12 ? 1 : $month+1 ?>&year=<?= $month+1 > 12 ? $year+1 : $year ?>">
                <button>Következő ➡</button>
            </a>
        </div>
    </div>

    <form method="get" style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;align-items:center;">
        <input type="hidden" name="month" value="<?= $month ?>">
        <input type="hidden" name="year" value="<?= $year ?>">

        <select name="category" style="width:auto;margin:0;">
            <option value="">Összes kategória</option>
            <?php foreach(["Oktatás","Sport","Szórakozás","Vizsga","Egyéb"] as $cat): ?>
                <option <?= $categoryFilter == $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
        </select>

        <select name="visibility" style="width:auto;margin:0;">
            <option value="">Összes láthatóság</option>
            <option value="private" <?= $visibilityFilter=='private' ? 'selected':'' ?>>Privát</option>
            <option value="class"   <?= $visibilityFilter=='class'   ? 'selected':'' ?>>Osztály</option>
            <option value="school"  <?= $visibilityFilter=='school'  ? 'selected':'' ?>>Iskola</option>
            <option value="public"  <?= $visibilityFilter=='public'  ? 'selected':'' ?>>Publikus</option>
        </select>

        <button style="width:auto;margin:0;padding:10px 24px;">Szűrés</button>

        <?php if($categoryFilter || $visibilityFilter): ?>
            <a href="?month=<?= $month ?>&year=<?= $year ?>" 
               style="font-size:13px;color:rgba(255,255,255,.4);">Szűrő törlése ✕</a>
        <?php endif; ?>
    </form>

    <div class="calendar-grid">

        <?php
        $days = ["Hétfő","Kedd","Szerda","Csütörtök","Péntek","Szombat","Vasárnap"];
        foreach ($days as $d) {
            echo "<div class='day-name'>$d</div>";
        }

        for ($i = 1; $i < $startDay; $i++) {
            echo "<div></div>";
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $isToday = ($day == date("j") && $month == date("m") && $year == date("Y")) ? "today" : "";

            echo "<div class='day $isToday'>";
            echo "<span class='day-number'>$day</span>";

            if (isset($eventsByDay[$day])) {
                foreach ($eventsByDay[$day] as $event) {
                    $colorClass = match($event["category"] ?? '') {
                        "Vizsga" => "red",
                        "Sport"  => "gold",
                        default  => ""
                    };

                    echo "<div class='event $colorClass'>";
                    echo htmlspecialchars($event["title"]);

                    if ($event["event_date"] == date("Y-m-d")) {
                        echo " <span style='opacity:.7'>(Ma)</span>";
                    }

                    if ($event["user_id"] == $_SESSION["user_id"]) {
                        echo "<br><a href='delete_event.php?id=" . $event["id"] . "' 
                              style='font-size:10px;opacity:.6;color:inherit;'>🗑 Törlés</a>";
                    }

                    echo "</div>";
                }
            }

            echo "</div>";
        }
        ?>

    </div>
</div>
