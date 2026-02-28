<?php
session_start();

// .env betöltése
foreach (file(__DIR__ . "/.env") as $line) {
    $line = trim($line);
    if ($line && !str_starts_with($line, "#")) putenv($line);
}

$host = getenv("DB_HOST");
$db   = "postgres";
$user = "postgres";
$pass = getenv("DB_PASS");
$port = "5432";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Adatbázis hiba: " . $e->getMessage());
}
?>