<?php
// Incluir la conexión a la base de datos
include 'db.php';
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

// Manejar la solicitud de un nuevo viaje
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passengerId = $_SESSION['user_id'];
    $pickup = $_POST['pickup'];
    $destination = $_POST['destination'];
    $distance = $_POST['distance'];
    $fare = $_POST['fare'];

    // Insertar el viaje en la base de datos
    $stmt = $conn->prepare("INSERT INTO trips (passenger_id, pickup_address, destination_address, distance, fare, status) VALUES (?, ?, ?, ?, ?, 'pendiente')");
    $stmt->bind_param("issdd", $passengerId, $pickup, $destination, $distance, $fare);
    if ($stmt->execute()) {
        $tripId = $conn->insert_id; // Obtener el ID del viaje
        header("Location: trackingpa.php?trip_id=$tripId");
        exit;
    } else {
        echo "Error al crear el viaje. Inténtelo nuevamente.";
    }
}
?>
