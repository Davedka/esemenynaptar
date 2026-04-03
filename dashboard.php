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
$year  = $_GET['year']  ?? date("Y");

$firstDayOfMonth = date("$year-$month-01");
$daysInMonth     = date("t", strtotime($firstDayOfMonth));
$startDay        = date("N", strtotime($firstDayOfMonth));

/* ── Több szűrő egyszerre ── */
$categoryFilters   = array_filter((array)($_GET['category']   ?? []));
$visibilityFilters = array_filter((array)($_GET['visibility'] ?? []));

$allCategories  = ["Oktatás","Sport","Szórakozás","Vizsga","Egyéb"];
$allVisibilities = [
    'private' => '🔒 Privát',
    'class'   => '🏫 Osztály',
    'school'  => '🏛 Iskola',
    'public'  => '🌐 Publikus',
];

/* Bejelentkezett user iskolájának lekérése */
$userStmt = $pdo->prepare("SELECT school FROM users WHERE id = ?");
$userStmt->execute([$_SESSION["user_id"]]);
$currentUser = $userStmt->fetch();

/* ── Lekérdezés több szűrővel ── */
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

if (!empty($categoryFilters)) {
    $placeholders = implode(',', array_fill(0, count($categoryFilters), '?'));
    $query .= " AND e.category IN ($placeholders)";
    $params = array_merge($params, array_values($categoryFilters));
}

if (!empty($visibilityFilters)) {
    $placeholders = implode(',', array_fill(0, count($visibilityFilters), '?'));
    $query .= " AND e.visibility IN ($placeholders)";
    $params = array_merge($params, array_values($visibilityFilters));
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

/* URL suffix a hónapnavigációhoz */
$filterSuffix = '';
foreach ($categoryFilters   as $v) $filterSuffix .= '&category[]='   . urlencode($v);
foreach ($visibilityFilters as $v) $filterSuffix .= '&visibility[]=' . urlencode($v);

$prevMonth = $month - 1 <= 0 ? 12 : $month - 1;
$prevYear  = $month - 1 <= 0 ? $year - 1 : $year;
$nextMonth = $month + 1 > 12 ? 1  : $month + 1;
$nextYear  = $month + 1 > 12 ? $year + 1 : $year;

$hasFilter = !empty($categoryFilters) || !empty($visibilityFilters);
?>
<!DOCTYPE html>
<html lang="hu">
<head><?php include "head.php"; ?></head>
<body>
<link rel="stylesheet" href="style.css">

<style>
/* ── Sidebar ── */
.sidebar {
    position:fixed; top:0; left:-260px; width:260px; height:100vh;
    background:rgba(8,8,8,.97); backdrop-filter:blur(24px);
    border-right:1px solid rgba(0,200,200,.15); z-index:500;
    transition:left .3s cubic-bezier(.22,.68,0,1.2);
    display:flex; flex-direction:column; padding-top:64px;
}
.sidebar.open { left:0; }
.sidebar-toggle {
    position:fixed; top:50%; left:0; transform:translateY(-50%); z-index:501;
    width:22px; height:56px; background:rgba(0,200,200,.15);
    border:1px solid rgba(0,200,200,.25); border-left:none;
    border-radius:0 8px 8px 0; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    transition:background .2s, left .3s cubic-bezier(.22,.68,0,1.2);
    color:var(--cyan); font-size:12px;
}
.sidebar-toggle:hover { background:rgba(0,200,200,.28); }
.sidebar-toggle.open  { left:260px; }
.sidebar-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:499;
    opacity:0; pointer-events:none; transition:opacity .3s;
}
.sidebar-overlay.open { opacity:1; pointer-events:all; }
.sidebar-header { padding:20px 24px 16px; border-bottom:1px solid rgba(255,255,255,.07); margin-bottom:8px; }
.sidebar-header .sidebar-title { font-family:'Playfair Display',serif; font-size:16px; color:white; font-weight:700; }
.sidebar-header .sidebar-subtitle { font-size:12px; color:rgba(255,255,255,.35); margin-top:2px; }
.sidebar-nav { flex:1; padding:0 12px; overflow-y:auto; }
.sidebar-nav a {
    display:flex; align-items:center; gap:12px; padding:11px 14px;
    border-radius:8px; font-size:14px; font-weight:500;
    color:rgba(255,255,255,.55); text-decoration:none;
    transition:all .18s ease; margin-bottom:2px;
}
.sidebar-nav a:hover { background:rgba(255,255,255,.07); color:white; }
.sidebar-nav a.active { background:rgba(0,200,200,.12); color:var(--cyan); border:1px solid rgba(0,200,200,.18); }
.sidebar-nav .nav-icon { font-size:17px; width:22px; text-align:center; }
.sidebar-nav .nav-section {
    font-size:10px; font-weight:600; text-transform:uppercase;
    letter-spacing:.8px; color:rgba(255,255,255,.22); padding:14px 14px 6px;
}
.sidebar-footer { padding:16px 12px; border-top:1px solid rgba(255,255,255,.07); }
.sidebar-footer a {
    display:flex; align-items:center; gap:12px; padding:10px 14px;
    border-radius:8px; font-size:13px; color:rgba(255,255,255,.40);
    text-decoration:none; transition:all .18s;
}
.sidebar-footer a:hover { background:rgba(255,255,255,.06); color:white; }

/* ── Chip szűrők ── */
.filter-bar {
    display:flex; flex-direction:column; gap:10px;
    margin-bottom:24px;
    background:rgba(255,255,255,.025);
    border:1px solid rgba(255,255,255,.07);
    border-radius:12px;
    padding:16px 20px;
}
.filter-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.filter-label {
    font-size:11px; font-weight:600; text-transform:uppercase;
    letter-spacing:.7px; color:rgba(255,255,255,.30); min-width:80px;
}
.chip-group { display:flex; gap:6px; flex-wrap:wrap; }
.chip {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 13px; border-radius:99px; font-size:12px; font-weight:600;
    cursor:pointer; user-select:none;
    border:1px solid rgba(255,255,255,.12);
    background:rgba(255,255,255,.04);
    color:rgba(255,255,255,.45);
    transition:all .18s ease;
}
.chip:hover { border-color:rgba(255,255,255,.25); color:rgba(255,255,255,.75); background:rgba(255,255,255,.07); }
.chip.active-cyan   { background:rgba(0,200,200,.14);  border-color:rgba(0,200,200,.40);  color:var(--cyan); }
.chip.active-red    { background:rgba(200,16,46,.14);  border-color:rgba(200,16,46,.40);  color:#ff6b82; }
.chip.active-gold   { background:rgba(200,151,42,.14); border-color:rgba(200,151,42,.40); color:#f0c76b; }
.chip.active-purple { background:rgba(120,80,200,.14); border-color:rgba(120,80,200,.40); color:#b39ddb; }
.chip.active-muted  { background:rgba(255,255,255,.10); border-color:rgba(255,255,255,.28); color:white; }
.filter-actions {
    display:flex; align-items:center; gap:10px;
    padding-top:10px; border-top:1px solid rgba(255,255,255,.06); margin-top:4px;
}
.filter-count { font-size:12px; color:rgba(255,255,255,.30); flex:1; }
.filter-count span { color:var(--cyan); font-weight:600; }
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
        <a href="dashboard.php" class="active"><span class="nav-icon">📅</span> Eseménynaptár</a>
        <a href="https://ticky-6r32.onrender.com"><span class="nav-icon">📋</span> Órarend</a>
        <div class="nav-section">Esemény</div>
        <a href="add_event.php"><span class="nav-icon">➕</span> Új esemény</a>
    </nav>
    <div class="sidebar-footer">
        <a href="delete_account.php" style="color:#ff6b82 !important;"><span class="nav-icon">🗑</span> Profil törlése</a>
        <a href="logout.php"><span class="nav-icon">🚪</span> Kijelentkezés</a>
    </div>
</div>
<div class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">›</div>

<script>
function toggleSidebar() {
    const s = document.getElementById('sidebar'),
          t = document.getElementById('sidebarToggle'),
          o = document.getElementById('sidebarOverlay');
    s.classList.toggle('open'); t.classList.toggle('open'); o.classList.toggle('open');
    t.textContent = s.classList.contains('open') ? '‹' : '›';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarToggle').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
    document.getElementById('sidebarToggle').textContent = '›';
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
            <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?><?= $filterSuffix ?>"><button>⬅ Előző</button></a>
            <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?><?= $filterSuffix ?>"><button>Következő ➡</button></a>
        </div>
    </div>

    <!-- ── Chip szűrősor ── -->
    <form method="get" id="filterForm">
        <input type="hidden" name="month" value="<?= $month ?>">
        <input type="hidden" name="year"  value="<?= $year ?>">

        <div class="filter-bar">

            <div class="filter-row">
                <span class="filter-label">Kategória</span>
                <div class="chip-group">
                    <?php
                    $catActiveClass = [
                        'Oktatás'    => 'active-cyan',
                        'Sport'      => 'active-gold',
                        'Szórakozás' => 'active-purple',
                        'Vizsga'     => 'active-red',
                        'Egyéb'      => 'active-muted',
                    ];
                    foreach ($allCategories as $cat):
                        $isActive = in_array($cat, $categoryFilters);
                        $cls = $isActive ? $catActiveClass[$cat] : '';
                    ?>
                        <label class="chip <?= $cls ?>">
                            <input type="checkbox" name="category[]"
                                   value="<?= htmlspecialchars($cat) ?>"
                                   <?= $isActive ? 'checked' : '' ?>
                                   onchange="updateChip(this, '<?= $catActiveClass[$cat] ?>')"
                                   style="display:none;">
                            <?= $cat ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-row">
                <span class="filter-label">Láthatóság</span>
                <div class="chip-group">
                    <?php foreach ($allVisibilities as $val => $label):
                        $isActive = in_array($val, $visibilityFilters);
                    ?>
                        <label class="chip <?= $isActive ? 'active-muted' : '' ?>">
                            <input type="checkbox" name="visibility[]"
                                   value="<?= $val ?>"
                                   <?= $isActive ? 'checked' : '' ?>
                                   onchange="updateChip(this, 'active-muted')"
                                   style="display:none;">
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($hasFilter): ?>
            <div class="filter-actions">
                <div class="filter-count">
                    Találat: <span><?= count($events) ?></span> esemény
                    <?php if (count($categoryFilters) + count($visibilityFilters) > 1): ?>
                        &mdash; <?= count($categoryFilters) + count($visibilityFilters) ?> aktív szűrő
                    <?php endif; ?>
                </div>
                <a href="?month=<?= $month ?>&year=<?= $year ?>"
                   style="font-size:12px;color:rgba(255,255,255,.35);padding:5px 12px;
                          border:1px solid rgba(255,255,255,.12);border-radius:99px;
                          transition:color .2s,border-color .2s;"
                   onmouseover="this.style.color='white';this.style.borderColor='rgba(255,255,255,.3)'"
                   onmouseout="this.style.color='rgba(255,255,255,.35)';this.style.borderColor='rgba(255,255,255,.12)'">
                    ✕ Összes törlése
                </a>
            </div>
            <?php endif; ?>

        </div>
    </form>

    <script>
    function updateChip(cb, activeClass) {
        const label = cb.closest('label');
        // összes active class eltávolítása
        ['active-cyan','active-red','active-gold','active-purple','active-muted']
            .forEach(c => label.classList.remove(c));
        if (cb.checked) label.classList.add(activeClass);
        // kis késleltetés hogy a vizuális visszajelzés látszódjon, aztán submit
        setTimeout(() => document.getElementById('filterForm').submit(), 120);
    }
    </script>

    <!-- ── Naptár rács ── -->
    <div class="calendar-grid">
        <?php
        $days = ["Hétfő","Kedd","Szerda","Csütörtök","Péntek","Szombat","Vasárnap"];
        foreach ($days as $d) echo "<div class='day-name'>$d</div>";

        for ($i = 1; $i < $startDay; $i++) echo "<div></div>";

        for ($day = 1; $day <= $daysInMonth; $day++):
            $isToday = ($day == date("j") && $month == date("m") && $year == date("Y")) ? "today" : "";
            $dateStr = $year."-".str_pad($month,2,"0",STR_PAD_LEFT)."-".str_pad($day,2,"0",STR_PAD_LEFT);
        ?>
            <div class="day <?= $isToday ?>" style="cursor:pointer;"
                 onclick="location.href='add_event.php?date=<?= $dateStr ?>'">
                <span class="day-number"><?= $day ?></span>
                <?php if (isset($eventsByDay[$day])): ?>
                    <?php foreach ($eventsByDay[$day] as $event):
                        $colorClass = match($event["category"] ?? '') {
                            "Vizsga" => "red", "Sport" => "gold", default => ""
                        };
                    ?>
                        <div class="event <?= $colorClass ?>" style="cursor:pointer;"
                             onclick="event.stopPropagation();location.href='event.php?id=<?= $event["id"] ?>'">
                            <?= htmlspecialchars($event["title"]) ?>
                            <?php if ($event["event_date"] == date("Y-m-d")): ?>
                                <span style="opacity:.7">(Ma)</span>
                            <?php endif; ?>
                            <?php if ($event["user_id"] == $_SESSION["user_id"]): ?>
                                <br>
                                <a href="delete_event.php?id=<?= $event["id"] ?>"
                                   onclick="event.stopPropagation();"
                                   style="font-size:10px;opacity:.6;color:inherit;">🗑 Törlés</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- ── Listanézet ── -->
<div style="position:relative;z-index:1;max-width:1160px;margin:0 auto 40px;padding:0 20px;">

    <div style="border-bottom:1px solid rgba(255,255,255,.07);padding-bottom:16px;margin-bottom:24px;
                display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <div class="page-title" style="font-size:22px;">Események ebben a hónapban</div>
            <div class="page-subtitle">
                <?php if ($hasFilter): ?>
                    Szűrt találatok &mdash; <strong style="color:var(--cyan)"><?= count($events) ?></strong> esemény
                <?php else: ?>
                    Összes esemény időrendben
                <?php endif; ?>
            </div>
        </div>

        <?php if ($hasFilter): ?>
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                <span style="font-size:12px;color:rgba(255,255,255,.30);">Aktív:</span>
                <?php foreach ($categoryFilters as $cf):
                    $bc = match($cf) { 'Vizsga'=>'badge-red','Sport'=>'badge-gold',default=>'badge-cyan' };
                ?>
                    <span class="badge <?= $bc ?>"><?= htmlspecialchars($cf) ?></span>
                <?php endforeach; ?>
                <?php foreach ($visibilityFilters as $vf): ?>
                    <span class="badge badge-muted"><?= $allVisibilities[$vf] ?? $vf ?></span>
                <?php endforeach; ?>
                <a href="?month=<?= $month ?>&year=<?= $year ?>"
                   style="font-size:12px;color:rgba(255,255,255,.30);padding:3px 10px;
                          border:1px solid rgba(255,255,255,.10);border-radius:99px;
                          transition:color .2s,border-color .2s;"
                   onmouseover="this.style.color='white';this.style.borderColor='rgba(255,255,255,.3)'"
                   onmouseout="this.style.color='rgba(255,255,255,.30)';this.style.borderColor='rgba(255,255,255,.10)'">
                    ✕ Törlés
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($events)): ?>
        <div style="padding:48px 0;text-align:center;">
            <div style="font-size:48px;margin-bottom:16px;opacity:.3;"><?= $hasFilter ? '🔍' : '📭' ?></div>
            <p style="color:rgba(255,255,255,.4);font-size:15px;margin-bottom:14px;">
                <?= $hasFilter
                    ? 'Nincs a szűrőknek megfelelő esemény ebben a hónapban.'
                    : 'Nincs esemény ' . $monthNames[(int)$month] . 'ban / ' . $monthNames[(int)$month] . 'ben.' ?>
            </p>
            <?php if ($hasFilter): ?>
                <a href="?month=<?= $month ?>&year=<?= $year ?>"
                   style="display:inline-flex;align-items:center;gap:6px;font-size:13px;
                          color:var(--cyan);padding:8px 18px;
                          border:1px solid rgba(0,200,200,.25);border-radius:8px;
                          transition:background .2s;"
                   onmouseover="this.style.background='rgba(0,200,200,.08)'"
                   onmouseout="this.style.background='transparent'">
                    Összes szűrő törlése →
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

    <?php else:
        usort($events, fn($a,$b) => strcmp($a["event_date"], $b["event_date"]));
        foreach ($events as $event):
            $borderColor = match($event["category"] ?? '') {
                "Vizsga" => "rgba(200,16,46,.30)",
                "Sport"  => "rgba(200,151,42,.30)",
                default  => "rgba(0,200,200,.18)"
            };
            $borderHover = match($event["category"] ?? '') {
                "Vizsga" => "rgba(200,16,46,.55)",
                "Sport"  => "rgba(200,151,42,.55)",
                default  => "rgba(0,200,200,.45)"
            };
            $dotColor = match($event["category"] ?? '') {
                "Vizsga" => "#ff6b82",
                "Sport"  => "#f0c76b",
                default  => "var(--cyan)"
            };
            $badgeClass = match($event["category"] ?? '') {
                "Vizsga" => "badge-red",
                "Sport"  => "badge-gold",
                default  => "badge-cyan"
            };
    ?>
        <div style="display:flex;align-items:stretch;margin-bottom:10px;
                    background:rgba(255,255,255,.03);border:1px solid <?= $borderColor ?>;
                    border-radius:12px;cursor:pointer;overflow:hidden;
                    transition:background .2s,border-color .2s;"
             onclick="location.href='event.php?id=<?= $event["id"] ?>'"
             onmouseover="this.style.background='rgba(255,255,255,.055)';this.style.borderColor='<?= $borderHover ?>'"
             onmouseout="this.style.background='rgba(255,255,255,.03)';this.style.borderColor='<?= $borderColor ?>'">

            <div style="width:4px;background:<?= $dotColor ?>;flex-shrink:0;"></div>

            <div style="min-width:72px;text-align:center;padding:16px 12px;
                        border-right:1px solid rgba(255,255,255,.06);">
                <div style="font-size:28px;font-weight:700;color:<?= $dotColor ?>;line-height:1;">
                    <?= date("j", strtotime($event["event_date"])) ?>
                </div>
                <div style="font-size:10px;color:rgba(255,255,255,.35);text-transform:uppercase;
                            letter-spacing:.6px;margin-top:2px;">
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
                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($event["category"]) ?></span>
                    <?php endif; ?>
                    <span class="badge badge-muted">
                        <?= $allVisibilities[$event["visibility"]] ?? $event["visibility"] ?>
                    </span>
                    <?php if($event["event_date"] == date("Y-m-d")): ?>
                        <span class="badge badge-cyan">● Ma</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($event["user_id"] == $_SESSION["user_id"]): ?>
                <div style="display:flex;align-items:center;padding:0 20px;
                            border-left:1px solid rgba(255,255,255,.06);">
                    <a href="delete_event.php?id=<?= $event["id"] ?>"
                       onclick="event.stopPropagation();"
                       style="color:rgba(255,255,255,.25);font-size:18px;
                              transition:color .2s;text-decoration:none;"
                       onmouseover="this.style.color='#ff6b82'"
                       onmouseout="this.style.color='rgba(255,255,255,.25)'">🗑</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; endif; ?>

</div>
</body>
</html>
