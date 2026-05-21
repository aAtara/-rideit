<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_admin.php");
    exit;
}

// Verificar que es administrador
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login_admin.php");
    exit;
}

// Verificar que la tabla existe
$tableExists = $conn->query("SHOW TABLES LIKE 'sos_alerts'");
$alerts = [];

if ($tableExists && $tableExists->num_rows > 0) {
    $result = $conn->query("
        SELECT s.id, s.trip_id, s.lat, s.lng, s.status, s.created_at,
               u.name AS usuario, u.phone AS telefono,
               t.pickup_address, t.destination_address
        FROM sos_alerts s
        JOIN users u ON s.user_id = u.id
        JOIN trips t ON s.trip_id = t.id
        ORDER BY s.created_at DESC
    ");
    if ($result) {
        $alerts = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas SOS - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); }
    </style>
</head>
<body class="min-h-screen text-gray-300">

    <header class="bg-red-800 text-white px-4 py-3 flex justify-between items-center shadow-lg">
        <h1 class="text-xl font-bold">🚨 Alertas SOS</h1>
        <a href="admin_panel.php" class="bg-white text-red-600 px-4 py-2 rounded-lg text-sm font-bold">Volver</a>
    </header>

    <main class="p-4 max-w-3xl mx-auto space-y-4">
        <?php if (empty($alerts)): ?>
            <div class="bg-gray-800 p-6 rounded-xl text-center">
                <p class="text-gray-400 text-lg">No hay alertas SOS registradas.</p>
            </div>
        <?php else: ?>
            <?php foreach ($alerts as $alert): ?>
                <div class="bg-gray-800 p-4 rounded-xl shadow-md border-l-4 border-red-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-red-400 font-bold text-lg">Alerta #<?php echo $alert['id']; ?></p>
                            <p class="text-sm"><strong>Usuario:</strong> <?php echo htmlspecialchars($alert['usuario']); ?></p>
                            <p class="text-sm"><strong>Telefono:</strong> <?php echo htmlspecialchars($alert['telefono']); ?></p>
                            <p class="text-sm"><strong>Viaje:</strong> <?php echo htmlspecialchars($alert['pickup_address']); ?> → <?php echo htmlspecialchars($alert['destination_address']); ?></p>
                            <p class="text-sm"><strong>Ubicacion GPS:</strong> <?php echo $alert['lat']; ?>, <?php echo $alert['lng']; ?></p>
                            <p class="text-sm"><strong>Fecha:</strong> <?php echo $alert['created_at']; ?></p>
                            <p class="text-sm"><strong>Estado:</strong>
                                <span class="<?php echo $alert['status'] === 'pendiente' ? 'text-yellow-400' : 'text-green-400'; ?> font-bold">
                                    <?php echo ucfirst($alert['status']); ?>
                                </span>
                            </p>
                        </div>
                        <a href="https://maps.google.com/?q=<?php echo $alert['lat']; ?>,<?php echo $alert['lng']; ?>" target="_blank"
                           class="bg-blue-500 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-blue-600">
                            Ver en mapa
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>
