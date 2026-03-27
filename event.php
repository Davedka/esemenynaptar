<?php
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$id = $_GET["id"] ?? null;

if (!$id) {
    header("Location: dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT e.*, u.fullname as creator_name, u.school as creator_school 
                        FROM events e 
                        JOIN users u ON e.user_id = u.id 
                        WHERE e.id = ? AND e.is_deleted = FALSE");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: dashboard.php");
    exit;
}

// Jogosultság ellenőrzés - privát eseményt csak a tulajdonos láthatja
if ($event["visibility"] == "private" && $event["user_id"] != $_SESSION["user_id"]) {
    header("Location: dashboard.php");
    exit;
}

$monthNames = [
    1=>"Január", 2=>"Február", 3=>"Március", 4=>"Április",
    5=>"Május", 6=>"Június", 7=>"Július", 8=>"Augusztus",
    9=>"Szeptember", 10=>"Október", 11=>"November", 12=>"December"
];

$dotColor = match($event["category"] ?? '') {
    "Vizsga" => "#ff6b82",
    "Sport"  => "#f0c76b",
    default  => "var(--cyan)"
};

$borderColor = match($event["category"] ?? '') {
    "Vizsga" => "rgba(200,16,46,.30)",
    "Sport"  => "rgba(200,151,42,.30)",
    default  => "rgba(0,200,200,.18)"
};

$badgeClass = match($event["category"] ?? '') {
    "Vizsga" => "badge-red",
    "Sport"  => "badge-gold",
    default  => "badge-cyan"
};
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

<div style="position:relative;z-index:1;max-width:720px;margin:40px auto;padding:0 20px;">

    <!-- Vissza gomb -->
    <a href="dashboard.php" style="display:inline-flex;align-items:center;gap:8px;
       color:rgba(255,255,255,.45);font-size:14px;margin-bottom:24px;transition:color .2s;"
       onmouseover="this.style.color='white'"
       onmouseout="this.style.color='rgba(255,255,255,.45)'">
        ⬅ Vissza a naptárhoz
    </a>

    <!-- Esemény kártya -->
    <div style="
        background:rgba(255,255,255,.03);
        border:1px solid <?= $borderColor ?>;
        border-radius:18px;
        overflow:hidden;
    ">
        <!-- Fejléc sáv -->
        <div style="
            height:4px;
            background:linear-gradient(90deg, transparent, <?= $dotColor ?>, transparent);
        "></div>

        <div style="padding:36px 40px;">

            <!-- Kategória és láthatóság badge -->
            <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
                <?php if($event["category"]): ?>
                    <span class="badge <?= $badgeClass ?>">
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

            <!-- Cím -->
            <h1 style="
                font-family:'Playfair Display',serif;
                font-size:32px;
                font-weight:700;
                color:white;
                margin-bottom:16px;
                line-height:1.2;
            ">
                <?= htmlspecialchars($event["title"]) ?>
            </h1>

            <!-- Dátum -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:28px;">
                <span style="font-size:28px;font-weight:700;color:<?= $dotColor ?>;">
                    <?= date("j", strtotime($event["event_date"])) ?>
                </span>
                <div>
                    <div style="font-size:15px;color:white;font-weight:500;">
                        <?= $monthNames[(int)date("n", strtotime($event["event_date"]))] ?>
                        <?= date("Y", strtotime($event["event_date"])) ?>
                    </div>
                    <div style="font-size:12px;color:rgba(255,255,255,.35);">
                        <?= ["Hétfő","Kedd","Szerda","Csütörtök","Péntek","Szombat","Vasárnap"][date("N", strtotime($event["event_date"]))-1] ?>
                    </div>
                </div>
            </div>

            <!-- Elválasztó -->
            <div style="height:1px;background:rgba(255,255,255,.07);margin-bottom:28px;"></div>

            <!-- Leírás -->
            <?php if(!empty($event["description"])): ?>
                <div style="margin-bottom:28px;">
                    <div style="font-size:12px;font-weight:600;text-transform:uppercase;
                                letter-spacing:.8px;color:rgba(255,255,255,.35);margin-bottom:10px;">
                        Leírás
                    </div>
                    <div style="font-size:15px;color:rgba(255,255,255,.80);line-height:1.7;">
                        <?= nl2br(htmlspecialchars($event["description"])) ?>
                    </div>
                </div>
                <div style="height:1px;background:rgba(255,255,255,.07);margin-bottom:28px;"></div>
            <?php endif; ?>

            <!-- Meta adatok -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:28px;">
                <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);
                            border-radius:10px;padding:16px;">
                    <div style="font-size:11px;color:rgba(255,255,255,.35);text-transform:uppercase;
                                letter-spacing:.7px;margin-bottom:6px;">Létrehozta</div>
                    <div style="font-size:14px;color:white;font-weight:500;">
                        <?= htmlspecialchars($event["creator_name"]) ?>
                    </div>
                </div>
                <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);
                            border-radius:10px;padding:16px;">
                    <div style="font-size:11px;color:rgba(255,255,255,.35);text-transform:uppercase;
                                letter-spacing:.7px;margin-bottom:6px;">Iskola</div>
                    <div style="font-size:14px;color:white;font-weight:500;">
                        <?= htmlspecialchars($event["creator_school"]) ?>
                    </div>
                </div>
            </div>

            <!-- Törlés gomb ha tulajdonos -->
            <?php if ($event["user_id"] == $_SESSION["user_id"]): ?>
                <div style="height:1px;background:rgba(255,255,255,.07);margin-bottom:24px;"></div>
                <a href="delete_event.php?id=<?= $event["id"] ?>">
                    <button style="
                        background:rgba(200,16,46,.12);
                        border-color:rgba(200,16,46,.28);
                        color:#ff6b82;
                        width:auto;
                        padding:10px 24px;
                    ">
                        🗑 Esemény törlése
                    </button>
                </a>
            <?php endif; ?>

        </div>
    </div>
</div>
