<?php
// Incluir la conexión a la base de datos
include 'db.php';

// Reiniciar las estadísticas diarias de todos los conductores
$stmt = $conn->prepare("
    UPDATE drivers 
    SET daily_trips = 0, daily_earnings = 0
");
$stmt->execute();

// Comprobar si se realizó correctamente
if ($stmt->affected_rows > 0) {
    echo "Estadísticas reiniciadas correctamente.";
} else {
    echo "No se encontraron registros para reiniciar o no se realizó el reinicio.";
}
?>
