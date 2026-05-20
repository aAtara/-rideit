<?php
include 'db.php';
session_start();

// Borrar el token remember-me de la base de datos
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
}

// Borrar la cookie remember-me del navegador
if (isset($_COOKIE['rideit_remember'])) {
    setcookie('rideit_remember', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Destruir la sesion
$_SESSION = [];
session_destroy();

header("Location: login_pasajero.php");
exit;
?>
