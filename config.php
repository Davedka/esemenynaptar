<?php

// ===== Adatbázis adatok =====
$host = getenv("DB_HOST") ?: "db.aywstgoezgowqqkrjoqq.supabase.co";
$port = getenv("DB_PORT") ?: "5432";
$db   = getenv("DB_NAME") ?: "postgres";
$user = getenv("DB_USER") ?: "postgres";
$pass = getenv("DB_PASSWORD") ?: "IDE_A_SUPABASE_DB_JELSZAVAD";

try {

    // ===== DSN =====
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";

    // ===== PDO kapcsolat =====
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

} catch (PDOException $e) {

    // Hibakezelés
    die("Adatbázis kapcsolat hiba: " . $e->getMessage());

}

?>
