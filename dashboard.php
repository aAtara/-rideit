<?php
include 'db.php';
include 'csrf.php';
session_start();

// Verificar si el usuario ha iniciado sesión como conductor
if (!isset($_SESSION['user_id'])) {
    header("Location: login_conductor.php");
    exit;
}

// Si es admin, redirigir al panel admin
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: admin_panel.php");
    exit;
}

// Si es pasajero, redirigir al dashboard de pasajero
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'pasajero') {
    header("Location: dashboardpa.php");
    exit;
}

// Obtener el nombre del usuario desde la sesión
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Determinar el saludo según la hora
date_default_timezone_set('America/Mexico_City');
$hour = (int)date("H");

if ($hour >= 0 && $hour < 6) {
    $greeting = "Buenas madrugadas";
} elseif ($hour >= 6 && $hour < 12) {
    $greeting = "Buenos días";
} elseif ($hour >= 12 && $hour < 19) {
    $greeting = "Buenas tardes";
} else {
    $greeting = "Buenas noches";
}

// Calcular viajes completados y ganancias del día
$stmt = $conn->prepare("
    SELECT COUNT(*) AS completed_trips, COALESCE(SUM(fare), 0) AS daily_earnings 
    FROM trips 
    WHERE driver_id = ? AND status = 'completado' AND DATE(completed_at) = CURDATE()
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

// Valores predeterminados si no hay datos
$completedTrips = $stats['completed_trips'] ?? 0;
$dailyEarnings = $stats['daily_earnings'] ?? 0.00;

// Promedio de calificacion del conductor
$stmtRating = $conn->prepare("SELECT ROUND(AVG(rating),1) AS promedio, COUNT(rating) AS total FROM trips WHERE driver_id = ? AND rating IS NOT NULL");
$stmtRating->bind_param("i", $userId);
$stmtRating->execute();
$ratingData = $stmtRating->get_result()->fetch_assoc();
$promedioRating = $ratingData['promedio'] ?? 0;
$totalRatings = $ratingData['total'] ?? 0;

// Foto de perfil del conductor
$driverPhoto = '';
$stmtPhoto = $conn->prepare("SELECT photo FROM users WHERE id = ?");
$stmtPhoto->bind_param("i", $userId);
$stmtPhoto->execute();
$photoRes = $stmtPhoto->get_result();
if ($photoRow = $photoRes->fetch_assoc()) {
    $driverPhoto = $photoRow['photo'] ?? '';
}

// Obtener solicitudes de viajes pendientes
$stmt = $conn->prepare("
    SELECT
        trips.id,
        trips.pickup_address AS pickup,
        trips.destination_address AS destination,
        trips.distance,
        trips.fare,
        trips.service_type,
        trips.payment_method,
        users.name AS passenger_name
    FROM trips
    INNER JOIN users ON trips.passenger_id = users.id
    WHERE trips.status = 'pendiente'
");
$stmt->execute();
$result = $stmt->get_result();
$trips = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <link rel="stylesheet" href="modal.css">
    <script>
        setInterval(() => {
            window.location.reload();
        }, 5000);
    </script>
</head>
<body class="bg-black text-white font-sans">

    <!-- Contenedor principal -->
    <div class="flex flex-col min-h-screen">

        <!-- Barra de navegación superior -->
        <header class="bg-gradient-to-r from-gray-800 via-gray-900 to-black text-white p-4 flex justify-between items-center shadow-md">
            <div class="flex items-center gap-3">
                <?php if ($driverPhoto): ?>
                    <img src="<?php echo htmlspecialchars($driverPhoto); ?>" alt="Perfil" class="w-10 h-10 rounded-full border-2 border-green-400 object-cover">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full border-2 border-green-400 bg-gray-700 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="white"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a8 8 0 00-8 8h16a8 8 0 00-8-8z"/></svg>
                    </div>
                <?php endif; ?>
                <h1 class="text-xl font-bold">🚖 RideIt - Conductores</h1>
            </div>
            <div class="flex space-x-4">
                <a href="mis_calificaciones.php" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-yellow-600 transition">
                    ⭐ Calificaciones
                </a>
                <a href="history.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-600 transition">
                    Historial
                </a>
                <a href="profile.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-600 transition">
                    Perfil
                </a>
                <button onclick="confirmLogout()" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-600 transition">
                    Cerrar Sesión
                </button>
            </div>
        </header>

        <!-- Contenido principal -->
        <main class="flex-1 overflow-y-auto p-4 pb-20">

            <!-- Saludo dinámico con foto -->
            <section id="greeting" class="mb-6 text-center">
                <?php if ($driverPhoto): ?>
                    <img src="<?php echo htmlspecialchars($driverPhoto); ?>" alt="Foto de perfil" class="w-20 h-20 rounded-full mx-auto mb-3 border-4 border-green-500 object-cover shadow-lg">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-full mx-auto mb-3 border-4 border-green-500 bg-gray-800 flex items-center justify-center shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="white"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a8 8 0 00-8 8h16a8 8 0 00-8-8z"/></svg>
                    </div>
                <?php endif; ?>
                <h2 class="text-2xl font-bold text-blue-500">
                    <?php echo htmlspecialchars("$greeting, $userName!"); ?>
                </h2>
                <p class="text-sm text-gray-400">Administra tus viajes, estado y ganancias facilmente.</p>
            </section>

            <!-- Dashboard -->
            <section id="dashboard" class="mb-10">
                <h2 class="text-xl font-bold mb-4">Dashboard</h2>
                <div class="grid grid-cols-1 gap-4">
                    <div class="bg-gray-800 p-4 rounded-lg shadow-md flex justify-between items-center">
                        <p class="text-sm font-medium text-gray-300">Viajes Completados</p>
                        <p class="text-2xl font-bold text-green-400"><?php echo $completedTrips; ?></p>
                    </div>
                    <div class="bg-gray-800 p-4 rounded-lg shadow-md flex justify-between items-center">
                        <p class="text-sm font-medium text-gray-300">Ganancias del Dia</p>
                        <p class="text-2xl font-bold text-green-400">$<?php echo number_format($dailyEarnings, 2); ?></p>
                    </div>
                    <a href="mis_calificaciones.php" class="bg-gray-800 p-4 rounded-lg shadow-md flex justify-between items-center hover:bg-gray-700 transition">
                        <p class="text-sm font-medium text-gray-300">Mi Calificacion</p>
                        <p class="text-2xl font-bold text-yellow-400">⭐ <?php echo $promedioRating ?: '0.0'; ?> <span class="text-sm text-gray-500">(<?php echo $totalRatings; ?>)</span></p>
                    </a>
                </div>
            </section>

            <!-- Solicitudes -->
            <section id="requests" class="mb-10">
                <h2 class="text-xl font-bold mb-4">Solicitudes</h2>
                <div class="space-y-4">
                    <?php if (count($trips) > 0): ?>
                        <?php foreach ($trips as $trip): ?>
                            <?php
                                $svcLabels = ['economico' => 'Economico', 'confort' => 'Confort'];
                                $payLabels = ['efectivo' => 'Efectivo', 'tarjeta_debito' => 'T. Debito', 'tarjeta_credito' => 'T. Credito'];
                            ?>
                            <div class="bg-gray-800 p-4 rounded-lg shadow-md">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="text-sm font-bold text-gray-200">Pasajero: <?php echo htmlspecialchars($trip['passenger_name']); ?></h3>
                                    <span class="text-xs bg-blue-600/30 text-blue-300 px-2 py-1 rounded-full"><?php echo htmlspecialchars($svcLabels[$trip['service_type'] ?? 'economico'] ?? 'Economico'); ?></span>
                                </div>
                                <p class="text-xs text-gray-400"><strong>Recoger en:</strong> <?php echo htmlspecialchars($trip['pickup']); ?></p>
                                <p class="text-xs text-gray-400"><strong>Destino:</strong> <?php echo htmlspecialchars($trip['destination']); ?></p>
                                <p class="text-xs text-gray-400"><strong>Distancia:</strong> <?php echo $trip['distance']; ?> km</p>
                                <p class="text-xs text-gray-400"><strong>Tarifa:</strong> $<?php echo $trip['fare']; ?> <span class="text-gray-500">(<?php echo htmlspecialchars($payLabels[$trip['payment_method'] ?? 'efectivo'] ?? 'Efectivo'); ?>)</span></p>
                                <div class="mt-4 flex justify-between">
                                    <form id="accept-<?php echo (int)$trip['id']; ?>" action="accept_trip.php" method="POST">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="trip_id" value="<?php echo (int)$trip['id']; ?>">
                                        <button type="button" onclick="confirmAccept(<?php echo (int)$trip['id']; ?>, '<?php echo htmlspecialchars(addslashes($trip['passenger_name'])); ?>')" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600 transition">
                                            Aceptar
                                        </button>
                                    </form>
                                    <form id="reject-<?php echo (int)$trip['id']; ?>" action="reject_trip.php" method="POST">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="trip_id" value="<?php echo (int)$trip['id']; ?>">
                                        <button type="button" onclick="confirmReject(<?php echo (int)$trip['id']; ?>, '<?php echo htmlspecialchars(addslashes($trip['passenger_name'])); ?>')" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600 transition">
                                            Rechazar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-400">No hay solicitudes pendientes.</p>
                    <?php endif; ?>
                </div>
            </section>

        </main>
    </div>

<script src="modal.js"></script>
<script>
function confirmAccept(tripId, passengerName) {
    RideIt.confirm({
        title: 'Aceptar viaje',
        message: '¿Deseas aceptar el viaje de <strong>' + passengerName + '</strong>? Seras redirigido al seguimiento.',
        type: 'success',
        confirmText: 'Aceptar viaje',
        confirmClass: 'btn-success',
        onConfirm: () => document.getElementById('accept-' + tripId).submit()
    });
}

function confirmReject(tripId, passengerName) {
    RideIt.confirm({
        title: 'Rechazar viaje',
        message: '¿Estas seguro de rechazar el viaje de <strong>' + passengerName + '</strong>? Esta accion no se puede deshacer.',
        type: 'danger',
        confirmText: 'Rechazar',
        onConfirm: () => document.getElementById('reject-' + tripId).submit()
    });
}

function confirmLogout() {
    RideIt.confirm({
        title: 'Cerrar sesion',
        message: '¿Deseas cerrar tu sesion? Tendras que volver a iniciar sesion.',
        type: 'warning',
        confirmText: 'Cerrar sesion',
        confirmClass: 'btn-danger',
        onConfirm: () => window.location.href = 'logout.php'
    });
}
</script>
</body>
</html>
