<?php
$host = "aws-0-eu-central-1.pooler.supabase.com";
$port = "6543";
$dbname = "postgres";
$user = "postgres.aywstgoezgowqqkrjoqq";
$password = "Gazitd0710";
try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "Sikeres kapcsolat!";
} catch (PDOException $e) {
    echo "Hiba: " . $e->getMessage();
}

