<?php
include 'db.php';
include 'csrf.php';
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
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

// Obtener solicitudes de viajes pendientes
$stmt = $conn->prepare("
    SELECT 
        trips.id, 
        trips.pickup_address AS pickup, 
        trips.destination_address AS destination, 
        trips.distance, 
        trips.fare, 
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
            <h1 class="text-xl font-bold">🚖 RideIt - Conductores</h1>
            <div class="flex space-x-4">
                <a href="history.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-600 transition">
                    Historial
                </a>
                <a href="profile.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-600 transition">
                    Perfil
                </a>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-600 transition">
                    Cerrar Sesión
                </a>
            </div>
        </header>

        <!-- Contenido principal -->
        <main class="flex-1 overflow-y-auto p-4 pb-20">

            <!-- Saludo dinámico -->
            <section id="greeting" class="mb-6 text-center">
                <h2 class="text-2xl font-bold text-blue-500">
                    <?php echo htmlspecialchars("$greeting, $userName!"); ?>
                </h2>
                <p class="text-sm text-gray-400">Administra tus viajes, estado y ganancias fácilmente.</p>
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
                        <p class="text-sm font-medium text-gray-300">Ganancias del Día</p>
                        <p class="text-2xl font-bold text-green-400">$<?php echo number_format($dailyEarnings, 2); ?></p>
                    </div>
                </div>
            </section>

            <!-- Solicitudes -->
            <section id="requests" class="mb-10">
                <h2 class="text-xl font-bold mb-4">Solicitudes</h2>
                <div class="space-y-4">
                    <?php if (count($trips) > 0): ?>
                        <?php foreach ($trips as $trip): ?>
                            <div class="bg-gray-800 p-4 rounded-lg shadow-md">
                                <h3 class="text-sm font-bold text-gray-200 mb-2">Pasajero: <?php echo htmlspecialchars($trip['passenger_name']); ?></h3>
                                <p class="text-xs text-gray-400"><strong>Recoger en:</strong> <?php echo htmlspecialchars($trip['pickup']); ?></p>
                                <p class="text-xs text-gray-400"><strong>Destino:</strong> <?php echo htmlspecialchars($trip['destination']); ?></p>
                                <p class="text-xs text-gray-400"><strong>Distancia:</strong> <?php echo $trip['distance']; ?> km</p>
                                <p class="text-xs text-gray-400"><strong>Tarifa:</strong> $<?php echo $trip['fare']; ?></p>
                                <div class="mt-4 flex justify-between">
                                    <form action="accept_trip.php" method="POST">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="trip_id" value="<?php echo (int)$trip['id']; ?>">
                                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600 transition">
                                            Aceptar
                                        </button>
                                    </form>
                                    <form action="reject_trip.php" method="POST">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="trip_id" value="<?php echo (int)$trip['id']; ?>">
                                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600 transition">
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

</body>
</html>
