<?php
// Incluir la conexión a la base de datos
include 'db.php';

session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login_conductor.php");
    exit;
}

// Obtener el ID del conductor desde la sesión
$driverId = $_SESSION['user_id'];

// Obtener el historial de viajes completados para este conductor
$stmt = $conn->prepare("
    SELECT t.id, t.completed_at, t.fare, t.pickup_address, t.destination_address,
           t.rating, t.service_type, t.payment_method, t.payment_status, u.name AS passenger_name
    FROM trips t
    JOIN users u ON t.passenger_id = u.id
    WHERE t.driver_id = ? AND t.status = 'completado'
    ORDER BY t.completed_at DESC
");
$stmt->bind_param("i", $driverId);
$stmt->execute();
$result = $stmt->get_result();
$trips = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Viajes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
</head>
<body class="bg-black text-white font-sans">

    <!-- Encabezado -->
    <header class="bg-gradient-to-r from-gray-800 via-gray-900 to-black text-white px-4 py-3 flex justify-between items-center shadow-md sticky top-0 z-50">
        <h1 class="text-lg font-bold truncate">🚖 Historial de Viajes</h1>
        <a href="dashboard.php" class="bg-blue-500 text-white px-3 py-2 rounded-md text-sm font-semibold hover:bg-blue-600 transition">
            Volver
        </a>
    </header>

    <!-- Contenido principal -->
    <main class="p-4 space-y-6">
        <h2 class="text-xl font-bold text-blue-500">📋 Historial</h2>
        <div class="space-y-4">
            <?php if (count($trips) > 0): ?>
                <?php foreach ($trips as $trip): ?>
                    <?php
                        $svcLabels = ['economico' => 'Economico', 'confort' => 'Confort'];
                        $payLabelsHist = ['efectivo' => 'Efectivo', 'tarjeta_debito' => 'T. Debito', 'tarjeta_credito' => 'T. Credito'];
                    ?>
                    <div class="bg-gray-800 p-4 rounded-lg shadow-md border-l-4 border-green-500">
                        <div class="flex justify-between items-start mb-1">
                            <p class="text-sm font-semibold text-gray-300">
                                <i class="fas fa-calendar-alt"></i> <strong>Fecha:</strong>
                                <?php
                                    echo !empty($trip['completed_at']) ?
                                        date("d/m/Y", strtotime($trip['completed_at'])) :
                                        "Fecha no disponible";
                                ?>
                            </p>
                            <span class="text-xs bg-green-600/30 text-green-300 px-2 py-1 rounded-full"><?php echo htmlspecialchars($svcLabels[$trip['service_type'] ?? 'economico'] ?? 'Economico'); ?></span>
                        </div>
                        <p class="text-sm text-gray-400">
                            <i class="fas fa-map-marker-alt"></i> <strong>Origen:</strong> <?php echo htmlspecialchars($trip['pickup_address'] ?? 'No disponible'); ?>
                        </p>
                        <p class="text-sm text-gray-400">
                            <i class="fas fa-flag-checkered"></i> <strong>Destino:</strong> <?php echo htmlspecialchars($trip['destination_address'] ?? 'No disponible'); ?>
                        </p>
                        <p class="text-sm text-gray-400">
                            <i class="fas fa-user"></i> <strong>Pasajero:</strong> <?php echo htmlspecialchars($trip['passenger_name']); ?>
                        </p>
                        <p class="text-sm text-gray-400">
                            <i class="fas fa-money-bill-wave"></i> <strong>Tarifa:</strong> $<?php echo number_format($trip['fare'], 2); ?>
                            <span class="text-xs text-gray-500 ml-1">(<?php echo htmlspecialchars($payLabelsHist[$trip['payment_method'] ?? 'efectivo'] ?? 'Efectivo'); ?>)</span>
                            <?php if (($trip['payment_status'] ?? '') === 'pagado'): ?>
                                <span class="text-xs bg-green-600/30 text-green-300 px-2 py-0.5 rounded-full ml-1">Pagado</span>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($trip['rating'])): ?>
                        <p class="text-sm text-yellow-400">
                            <strong>Calificacion:</strong> <?php echo str_repeat('⭐', (int)$trip['rating']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-sm text-gray-400">No tienes viajes completados aún.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-gray-900 text-center py-4 text-sm text-gray-400">
        © 2025 RideIt Conductores. Todos los derechos reservados.
    </footer>

    <!-- Script para íconos -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
