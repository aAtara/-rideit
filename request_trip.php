<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        header("Location: uberx.php?error=invalid_token");
        exit;
    }

    $passengerId = $_SESSION['user_id'];
    $pickup = trim($_POST['pickup']);
    $destination = trim($_POST['destination']);
    $distance = (float)$_POST['distance'];
    $fare = (float)$_POST['fare'];

    if (empty($pickup) || empty($destination) || $distance <= 0 || $fare <= 0) {
        header("Location: uberx.php?error=invalid_data");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO trips (passenger_id, pickup_address, destination_address, distance, fare, status) VALUES (?, ?, ?, ?, ?, 'pendiente')");
    $stmt->bind_param("issdd", $passengerId, $pickup, $destination, $distance, $fare);
    if ($stmt->execute()) {
        $tripId = $conn->insert_id;
        header("Location: trackingpa.php?trip_id=$tripId");
        exit;
    } else {
        echo "Error al crear el viaje. Intentelo nuevamente.";
    }
}
?>
