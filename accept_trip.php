<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrfToken()) {
    header("Location: dashboard.php");
    exit;
}

$tripId = isset($_POST['trip_id']) ? (int)$_POST['trip_id'] : 0;
$driverId = $_SESSION['user_id'];

if (!$tripId) {
    die("ID de viaje no especificado");
}

$stmt = $conn->prepare("UPDATE trips SET status = 'asignado', driver_id = ? WHERE id = ? AND status = 'pendiente'");
$stmt->bind_param("ii", $driverId, $tripId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    header("Location: track_driver.php?trip_id=$tripId");
    exit;
} else {
    header("Location: dashboard.php?error=trip_taken");
    exit;
}
?>
