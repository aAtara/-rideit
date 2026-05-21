<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login_admin.php");
    exit;
}

$filtro = $_GET['estado'] ?? 'todos';
$where = "";
if ($filtro === 'pendiente') $where = "WHERE t.status = 'pendiente'";
elseif ($filtro === 'asignado') $where = "WHERE t.status = 'asignado'";
elseif ($filtro === 'completado') $where = "WHERE t.status = 'completado'";
elseif ($filtro === 'en_destino') $where = "WHERE t.status = 'en_destino'";

$result = $conn->query("
    SELECT t.id, t.pickup_address, t.destination_address, t.distance, t.fare, t.status,
           t.service_type, t.payment_method, t.payment_status,
           t.rating, t.created_at, t.completed_at,
           p.name AS pasajero, d.name AS conductor
    FROM trips t
    LEFT JOIN users p ON t.passenger_id = p.id
    LEFT JOIN users d ON t.driver_id = d.id
    $where
    ORDER BY t.created_at DESC
    LIMIT 100
");
$viajes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$statusColors = [
    'pendiente' => 'bg-yellow-600/30 text-yellow-400',
    'asignado' => 'bg-blue-600/30 text-blue-400',
    'afuera' => 'bg-orange-600/30 text-orange-400',
    'en_destino' => 'bg-purple-600/30 text-purple-400',
    'completado' => 'bg-green-600/30 text-green-400',
    'rechazado' => 'bg-red-600/30 text-red-400',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viajes - Admin RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .card { background: #1e293b; border: 1px solid #334155; }
    </style>
</head>
<body class="font-sans min-h-screen">

    <header class="bg-gradient-to-r from-purple-900 to-indigo-900 text-white px-4 py-4 flex justify-between items-center shadow-lg">
        <div class="flex items-center gap-3">
            <a href="admin_panel.php" class="text-purple-300 hover:text-white text-sm">← Dashboard</a>
            <h1 class="text-xl font-bold">🗺️ Historial de Viajes</h1>
        </div>
        <span class="text-sm text-purple-300"><?php echo count($viajes); ?> viajes</span>
    </header>

    <main class="p-4 md:p-8 max-w-6xl mx-auto pb-24">

        <!-- Filtros -->
        <div class="flex flex-wrap gap-2 mb-6">
            <a href="admin_viajes.php?estado=todos" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filtro==='todos'?'bg-indigo-600 text-white':'bg-gray-800 text-gray-400'; ?>">Todos</a>
            <a href="admin_viajes.php?estado=pendiente" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filtro==='pendiente'?'bg-yellow-600 text-white':'bg-gray-800 text-gray-400'; ?>">Pendientes</a>
            <a href="admin_viajes.php?estado=asignado" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filtro==='asignado'?'bg-blue-600 text-white':'bg-gray-800 text-gray-400'; ?>">Asignados</a>
            <a href="admin_viajes.php?estado=en_destino" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filtro==='en_destino'?'bg-purple-600 text-white':'bg-gray-800 text-gray-400'; ?>">En Destino</a>
            <a href="admin_viajes.php?estado=completado" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filtro==='completado'?'bg-green-600 text-white':'bg-gray-800 text-gray-400'; ?>">Completados</a>
        </div>

        <!-- Tabla -->
        <div class="card rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-800 text-gray-300">
                        <tr>
                            <th class="px-3 py-3 text-left">ID</th>
                            <th class="px-3 py-3 text-left">Pasajero</th>
                            <th class="px-3 py-3 text-left">Conductor</th>
                            <th class="px-3 py-3 text-left">Origen</th>
                            <th class="px-3 py-3 text-left">Destino</th>
                            <th class="px-3 py-3 text-left">Dist.</th>
                            <th class="px-3 py-3 text-left">Tarifa</th>
                            <th class="px-3 py-3 text-left">Estado</th>
                            <th class="px-3 py-3 text-left">Calif.</th>
                            <th class="px-3 py-3 text-left">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($viajes)): ?>
                            <tr><td colspan="10" class="px-4 py-8 text-center text-gray-500">No hay viajes registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($viajes as $v): ?>
                                <tr class="border-t border-gray-700 hover:bg-gray-800/50">
                                    <td class="px-3 py-3 text-gray-400"><?php echo $v['id']; ?></td>
                                    <td class="px-3 py-3 text-white"><?php echo htmlspecialchars($v['pasajero'] ?? '-'); ?></td>
                                    <td class="px-3 py-3 text-gray-300"><?php echo htmlspecialchars($v['conductor'] ?? 'Sin asignar'); ?></td>
                                    <td class="px-3 py-3 text-gray-400 text-xs max-w-xs truncate"><?php echo htmlspecialchars($v['pickup_address']); ?></td>
                                    <td class="px-3 py-3 text-gray-400 text-xs max-w-xs truncate"><?php echo htmlspecialchars($v['destination_address']); ?></td>
                                    <td class="px-3 py-3"><?php echo $v['distance']; ?> km</td>
                                    <td class="px-3 py-3 text-green-400 font-bold">$<?php echo number_format($v['fare'], 2); ?></td>
                                    <td class="px-3 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $statusColors[$v['status']] ?? 'bg-gray-600/30 text-gray-400'; ?>">
                                            <?php echo ucfirst(str_replace('_',' ',$v['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <?php if ($v['rating']): ?>
                                            <span class="text-yellow-400">⭐ <?php echo $v['rating']; ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-600">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-3 text-gray-400 text-xs"><?php echo date('d/m/Y H:i', strtotime($v['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Mobile nav -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-700 flex justify-around py-2 z-50">
        <a href="admin_panel.php" class="text-center text-xs text-gray-400 p-2">📊<br>Inicio</a>
        <a href="admin_usuarios.php" class="text-center text-xs text-gray-400 p-2">👥<br>Usuarios</a>
        <a href="admin_alertas.php" class="text-center text-xs text-gray-400 p-2">🚨<br>SOS</a>
        <a href="admin_tarifas.php" class="text-center text-xs text-gray-400 p-2">💰<br>Tarifas</a>
    </div>

</body>
</html>
