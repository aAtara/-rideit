<?php
// Incluir la conexión a la base de datos
include 'db.php';

session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login_conductor.php");
    exit;
}

// Obtener el ID del viaje y el ID del conductor desde la sesión
$tripId = $_POST['trip_id'] ?? null;
$driverId = $_SESSION['user_id'];

if ($tripId) {
    // Actualizar el estado del viaje a "completado"
    $stmt = $conn->prepare("
        UPDATE trips 
        SET status = 'completado', completed_at = NOW() 
        WHERE id = ? AND driver_id = ?
    ");
    $stmt->bind_param("ii", $tripId, $driverId);
    $stmt->execute();

    // Comprobar si se actualizó correctamente
    if ($stmt->affected_rows > 0) {
        // Actualizar estadísticas del conductor
        $stmt = $conn->prepare("
            UPDATE drivers 
            SET daily_trips = daily_trips + 1, daily_earnings = daily_earnings + (
                SELECT fare FROM trips WHERE id = ?
            ) 
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $tripId, $driverId);
        $stmt->execute();

        header("Location: dashboard.php");
    } else {
        echo "Error al completar el viaje.";
    }
} else {
    echo "ID de viaje no especificado.";
}
?>
