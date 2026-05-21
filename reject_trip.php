<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_conductor.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrfToken()) {
    header("Location: dashboard.php");
    exit;
}

$tripId = isset($_POST['trip_id']) ? (int)$_POST['trip_id'] : 0;
$driverId = $_SESSION['user_id'];

if (!$tripId) {
    header("Location: dashboard.php?error=no_trip");
    exit;
}

// Solo rechazar viajes que esten pendientes
$stmt = $conn->prepare("UPDATE trips SET status = 'rechazado' WHERE id = ? AND status = 'pendiente'");
$stmt->bind_param("i", $tripId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    header("Location: dashboard.php?rechazado=1");
} else {
    header("Location: dashboard.php?error=reject_failed");
}
exit;
?>
