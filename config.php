<?php

$host = "aws-0-eu-central-1.pooler.supabase.com";
$port = "6543"; // fontos! pooling port
$db   = "postgres";
$user = "postgres.aywstgoezgowqqkrjoqq";
$pass = "A_JELSZAVAD";

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Adatbázis kapcsolat hiba: " . $e->getMessage());
}

?>


