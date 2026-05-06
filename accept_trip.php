<?php
include 'db.php';
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Validar los datos enviados por POST
$tripId = $_POST['trip_id'] ?? null;
$driverId = $_SESSION['user_id'];

if (!$tripId) {
    die("ID de viaje no especificado");
}

$status = 'asignado'; // Este valor ya debe estar permitido en la columna `status`

// Actualizar el estado del viaje y asignar el conductor
$stmt = $conn->prepare("UPDATE trips SET status = ?, driver_id = ? WHERE id = ?");
$stmt->bind_param("sii", $status, $driverId, $tripId);

if ($stmt->execute()) {
    echo "Viaje aceptado exitosamente.";
    // Redirigir al panel de seguimiento o actualizar el estado
    header("Location: track_driver.php?trip_id=$tripId");
    exit;
} else {
    echo "Error al aceptar el viaje.";
}
?>
