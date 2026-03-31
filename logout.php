<?php
require "config.php";

// Remember me cookie törlése
if (isset($_COOKIE["remember_token"])) {
    $pdo->prepare("UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE remember_token = ?")
        ->execute([$_COOKIE["remember_token"]]);
    setcookie("remember_token", "", time() - 3600, "/");
}

session_destroy();
header("Location: login.php");
exit;
