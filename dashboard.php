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

/* Szűrő URL suffix segédfüggvény */
$filterSuffix = ($categoryFilter ? '&category='.urlencode($categoryFilter) : '')
              . ($visibilityFilter ? '&visibility='.urlencode($visibilityFilter) : '');

$prevMonth = $month - 1 <= 0 ? 12 : $month - 1;
$prevYear  = $month - 1 <= 0 ? $year - 1 : $year;
$nextMonth = $month + 1 > 12 ? 1 : $month + 1;
$nextYear  = $month + 1 > 12 ? $year + 1 : $year;
?>

<!DOCTYPE html>
<html lang="hu">
<head><?php include "head.php"; ?></head>
<body>

<link rel="stylesheet" href="style.css">

<style>
.sidebar {
    position: fixed;
    top: 0;
    left: -260px;
    width: 260px;
    height: 100vh;
    background: rgba(8,8,8,.97);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border-right: 1px solid rgba(0,200,200,.15);
    z-index: 500;
    transition: left .3s cubic-bezier(.22,.68,0,1.2);
    display: flex;
    flex-direction: column;
    padding-top: 64px;
}

.sidebar.open { left: 0; }

.sidebar-toggle {
    position: fixed;
    top: 50%;
    left: 0;
    transform: translateY(-50%);
    z-index: 501;
    width: 22px;
    height: 56px;
    background: rgba(0,200,200,.15);
    border: 1px solid rgba(0,200,200,.25);
    border-left: none;
    border-radius: 0 8px 8px 0;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .2s, left .3s cubic-bezier(.22,.68,0,1.2);
    color: var(--cyan);
    font-size: 12px;
}

.sidebar-toggle:hover { background: rgba(0,200,200,.28); }
.sidebar-toggle.open  { left: 260px; }

.sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 499;
    opacity: 0;
    pointer-events: none;
    transition: opacity .3s;
}

.sidebar-overlay.open { opacity: 1; pointer-events: all; }

.sidebar-header {
    padding: 20px 24px 16px;
    border-bottom: 1px solid rgba(255,255,255,.07);
    margin-bottom: 8px;
}

.sidebar-header .sidebar-title {
    font-family: 'Playfair Display', serif;
    font-size: 16px;
    color: white;
    font-weight: 700;
}

.sidebar-header .sidebar-subtitle {
    font-size: 12px;
    color: rgba(255,255,255,.35);
    margin-top: 2px;
}

.sidebar-nav { flex: 1; padding: 0 12px; overflow-y: auto; }

.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 14px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: rgba(255,255,255,.55);
    text-decoration: none;
    transition: all .18s ease;
    margin-bottom: 2px;
}

.sidebar-nav a:hover {
    background: rgba(255,255,255,.07);
    color: white;
}

.sidebar-nav a.active {
    background: rgba(0,200,200,.12);
    color: var(--cyan);
    border: 1px solid rgba(0,200,200,.18);
}

.sidebar-nav .nav-icon { font-size: 17px; width: 22px; text-align: center; }

.sidebar-nav .nav-section {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: rgba(255,255,255,.22);
    padding: 14px 14px 6px;
}

.sidebar-footer {
    padding: 16px 12px;
    border-top: 1px solid rgba(255,255,255,.07);
}

.sidebar-footer a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    color: rgba(255,255,255,.40);
    text-decoration: none;
    transition: all .18s;
}

.sidebar-footer a:hover { background: rgba(255,255,255,.06); color: white; }
</style>

<div class="top-line"></div>
<div class="orb-br"></div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-title">MSZC Gépészeti</div>
        <div class="sidebar-subtitle"><?= htmlspecialchars($_SESSION["fullname"]) ?></div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Főmenü</div>
        <a href="dashboard.php" class="active">
            <span class="nav-icon">📅</span> Eseménynaptár
        </a>
        <a href="https://ticky-6r32.onrender.com">
            <span class="nav-icon">📋</span> Órarend
        </a>

        <div class="nav-section">Esemény</div>
        <a href="add_event.php">
            <span class="nav-icon">➕</span> Új esemény
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="delete_account.php" style="color:#ff6b82 !important;">
            <span class="nav-icon">🗑</span> Profil törlése
        </a>
        <a href="logout.php">
            <span class="nav-icon">🚪</span> Kijelentkezés
        </a>
    </div>
</div>

<div class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">›</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggle  = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('open');
    toggle.classList.toggle('open');
    overlay.classList.toggle('open');
    toggle.textContent = sidebar.classList.contains('open') ? '‹' : '›';
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggle  = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.remove('open');
    toggle.classList.remove('open');
    overlay.classList.remove('open');
    toggle.textContent = '›';
}
</script>

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
            <!-- Szűrők megmaradnak hónapváltáskor -->
            <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?><?= $filterSuffix ?>">
                <button>⬅ Előző</button>
            </a>
            <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?><?= $filterSuffix ?>">
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
            $dateStr = $year . "-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-" . str_pad($day, 2, "0", STR_PAD_LEFT);

            echo "<div class='day $isToday' style='cursor:pointer;' onclick=\"location.href='add_event.php?date=$dateStr'\">";
            echo "<span class='day-number'>$day</span>";

            if (isset($eventsByDay[$day])) {
                foreach ($eventsByDay[$day] as $event) {
                    $colorClass = match($event["category"] ?? '') {
                        "Vizsga" => "red",
                        "Sport"  => "gold",
                        default  => ""
                    };

                    echo "<div class='event $colorClass' style='cursor:pointer;'
                          onclick=\"event.stopPropagation();location.href='event.php?id=" . $event["id"] . "'\">";
                    echo htmlspecialchars($event["title"]);

                    if ($event["event_date"] == date("Y-m-d")) {
                        echo " <span style='opacity:.7'>(Ma)</span>";
                    }

                    if ($event["user_id"] == $_SESSION["user_id"]) {
                        echo "<br><a href='delete_event.php?id=" . $event["id"] . "'
                              onclick='event.stopPropagation();'
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

<!-- Események listája -->
<div style="position:relative;z-index:1;max-width:1160px;margin:0 auto 40px;padding:0 20px;">

    <!-- Lista fejléc aktív szűrő jelzéssel -->
    <div style="border-bottom:1px solid rgba(255,255,255,.07);padding-bottom:16px;margin-bottom:24px;
                display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <div class="page-title" style="font-size:22px;">Események ebben a hónapban</div>
            <div class="page-subtitle">
                <?php if ($categoryFilter || $visibilityFilter): ?>
                    Szűrt találatok &mdash; <?= count($events) ?> esemény
                <?php else: ?>
                    Összes esemény időrendben
                <?php endif; ?>
            </div>
        </div>

        <?php if ($categoryFilter || $visibilityFilter): ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <span style="font-size:12px;color:rgba(255,255,255,.35);">Aktív szűrők:</span>

                <?php if ($categoryFilter): ?>
                    <?php $catColor = match($categoryFilter) {
                        'Vizsga' => 'badge-red',
                        'Sport'  => 'badge-gold',
                        default  => 'badge-cyan'
                    }; ?>
                    <span class="badge <?= $catColor ?>">
                        <?= htmlspecialchars($categoryFilter) ?>
                    </span>
                <?php endif; ?>

                <?php if ($visibilityFilter): ?>
                    <span class="badge badge-muted">
                        <?= match($visibilityFilter) {
                            'private' => '🔒 Privát',
                            'class'   => '🏫 Osztály',
                            'school'  => '🏛 Iskola',
                            'public'  => '🌐 Publikus',
                            default   => $visibilityFilter
                        } ?>
                    </span>
                <?php endif; ?>

                <a href="?month=<?= $month ?>&year=<?= $year ?>"
                   style="font-size:12px;color:rgba(255,255,255,.35);padding:3px 10px;
                          border:1px solid rgba(255,255,255,.12);border-radius:99px;
                          transition:color .2s,border-color .2s;"
                   onmouseover="this.style.color='white';this.style.borderColor='rgba(255,255,255,.3)'"
                   onmouseout="this.style.color='rgba(255,255,255,.35)';this.style.borderColor='rgba(255,255,255,.12)'">
                    ✕ Törlés
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($events)): ?>
        <!-- Üres állapot szűrőfüggő üzenettel -->
        <div style="padding:48px 0;text-align:center;">
            <div style="font-size:48px;margin-bottom:16px;opacity:.3;">
                <?= ($categoryFilter || $visibilityFilter) ? '🔍' : '📭' ?>
            </div>
            <p style="color:rgba(255,255,255,.4);font-size:15px;margin-bottom:12px;">
                <?php if ($categoryFilter || $visibilityFilter): ?>
                    Nincs a szűrőknek megfelelő esemény ebben a hónapban.
                <?php else: ?>
                    Nincs esemény <?= $monthNames[(int)$month] ?>ban / <?= $monthNames[(int)$month] ?>ben.
                <?php endif; ?>
            </p>
            <?php if ($categoryFilter || $visibilityFilter): ?>
                <a href="?month=<?= $month ?>&year=<?= $year ?>"
                   style="display:inline-flex;align-items:center;gap:6px;font-size:13px;
                          color:var(--cyan);padding:8px 18px;
                          border:1px solid rgba(0,200,200,.25);border-radius:8px;
                          transition:background .2s;"
                   onmouseover="this.style.background='rgba(0,200,200,.08)'"
                   onmouseout="this.style.background='transparent'">
                    Szűrő törlése →
                </a>
            <?php else: ?>
                <a href="add_event.php"
                   style="display:inline-flex;align-items:center;gap:6px;font-size:13px;
                          color:var(--cyan);padding:8px 18px;
                          border:1px solid rgba(0,200,200,.25);border-radius:8px;
                          transition:background .2s;"
                   onmouseover="this.style.background='rgba(0,200,200,.08)'"
                   onmouseout="this.style.background='transparent'">
                    ➕ Új esemény hozzáadása
                </a>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <?php
        usort($events, fn($a, $b) => strcmp($a["event_date"], $b["event_date"]));
        ?>

        <?php foreach ($events as $event): ?>
            <?php
            $colorClass = match($event["category"] ?? '') {
                "Vizsga" => "red",
                "Sport"  => "gold",
                default  => ""
            };
            $borderColor = match($event["category"] ?? '') {
                "Vizsga" => "rgba(200,16,46,.30)",
                "Sport"  => "rgba(200,151,42,.30)",
                default  => "rgba(0,200,200,.18)"
            };
            $borderColorHover = match($event["category"] ?? '') {
                "Vizsga" => "rgba(200,16,46,.55)",
                "Sport"  => "rgba(200,151,42,.55)",
                default  => "rgba(0,200,200,.45)"
            };
            $dotColor = match($event["category"] ?? '') {
                "Vizsga" => "#ff6b82",
                "Sport"  => "#f0c76b",
                default  => "var(--cyan)"
            };
            ?>

            <div style="
                display:flex;
                align-items:stretch;
                gap:0;
                margin-bottom:10px;
                background:rgba(255,255,255,.03);
                border:1px solid <?= $borderColor ?>;
                border-radius:12px;
                cursor:pointer;
                transition:background .2s, border-color .2s;
                overflow:hidden;
            "
            onclick="location.href='event.php?id=<?= $event["id"] ?>'"
            onmouseover="this.style.background='rgba(255,255,255,.055)';this.style.borderColor='<?= $borderColorHover ?>'"
            onmouseout="this.style.background='rgba(255,255,255,.03)';this.style.borderColor='<?= $borderColor ?>'">

                <div style="width:4px;background:<?= $dotColor ?>;flex-shrink:0;"></div>

                <div style="min-width:72px;text-align:center;padding:16px 12px;border-right:1px solid rgba(255,255,255,.06);">
                    <div style="font-size:28px;font-weight:700;color:<?= $dotColor ?>;line-height:1;">
                        <?= date("j", strtotime($event["event_date"])) ?>
                    </div>
                    <div style="font-size:10px;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.6px;margin-top:2px;">
                        <?= $monthNames[(int)date("n", strtotime($event["event_date"]))] ?>
                    </div>
                    <div style="font-size:10px;color:rgba(255,255,255,.25);margin-top:2px;">
                        <?= ["H","K","Sze","Cs","P","Szo","V"][date("N", strtotime($event["event_date"]))-1] ?>
                    </div>
                </div>

                <div style="flex:1;padding:16px 20px;">
                    <div style="font-size:15px;font-weight:600;color:white;margin-bottom:8px;">
                        <?= htmlspecialchars($event["title"]) ?>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php if($event["category"]): ?>
                            <span class="badge <?= $colorClass == 'red' ? 'badge-red' : ($colorClass == 'gold' ? 'badge-gold' : 'badge-cyan') ?>">
                                <?= htmlspecialchars($event["category"]) ?>
                            </span>
                        <?php endif; ?>
                        <span class="badge badge-muted">
                            <?= match($event["visibility"]) {
                                'private' => '🔒 Privát',
                                'class'   => '🏫 Osztály',
                                'school'  => '🏛 Iskola',
                                'public'  => '🌐 Publikus',
                                default   => $event["visibility"]
                            } ?>
                        </span>
                        <?php if($event["event_date"] == date("Y-m-d")): ?>
                            <span class="badge badge-cyan">● Ma</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($event["user_id"] == $_SESSION["user_id"]): ?>
                    <div style="display:flex;align-items:center;padding:0 20px;border-left:1px solid rgba(255,255,255,.06);">
                        <a href="delete_event.php?id=<?= $event["id"] ?>"
                           onclick="event.stopPropagation();"
                           style="color:rgba(255,255,255,.25);font-size:18px;transition:color .2s;text-decoration:none;"
                           onmouseover="this.style.color='#ff6b82'"
                           onmouseout="this.style.color='rgba(255,255,255,.25)'">
                            🗑
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

</body>
</html>
