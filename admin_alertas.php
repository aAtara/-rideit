<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login_admin.php");
    exit;
}

$mensaje = "";

// Crear tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS sos_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    user_id INT NOT NULL,
    lat DOUBLE,
    lng DOUBLE,
    status VARCHAR(20) DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Cambiar estado de alerta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alert_id'])) {
    if (validateCsrfToken()) {
        $alertId = (int)$_POST['alert_id'];
        $newStatus = $_POST['new_status'];
        if (in_array($newStatus, ['atendida', 'pendiente', 'falsa_alarma'])) {
            $stmt = $conn->prepare("UPDATE sos_alerts SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $alertId);
            $stmt->execute();
            $mensaje = "Estado de alerta actualizado.";
        }
    }
}

// Obtener alertas
$filtroEstado = $_GET['estado'] ?? 'todos';
$where = "";
if ($filtroEstado === 'pendiente') $where = "WHERE s.status = 'pendiente'";
elseif ($filtroEstado === 'atendida') $where = "WHERE s.status = 'atendida'";

$alerts = [];
$result = $conn->query("
    SELECT s.id, s.trip_id, s.lat, s.lng, s.status, s.created_at,
           u.name AS usuario, u.phone AS telefono, u.email,
           t.pickup_address, t.destination_address, t.status AS trip_status,
           d.name AS conductor_name
    FROM sos_alerts s
    JOIN users u ON s.user_id = u.id
    JOIN trips t ON s.trip_id = t.id
    LEFT JOIN users d ON t.driver_id = d.id
    $where
    ORDER BY s.created_at DESC
");
if ($result) {
    $alerts = $result->fetch_all(MYSQLI_ASSOC);
}

$totalPendientes = $conn->query("SELECT COUNT(*) AS c FROM sos_alerts WHERE status='pendiente'")->fetch_assoc()['c'];
$totalAtendidas = $conn->query("SELECT COUNT(*) AS c FROM sos_alerts WHERE status='atendida'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas SOS - Admin RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <link rel="stylesheet" href="modal.css">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .card { background: #1e293b; border: 1px solid #334155; }
        @keyframes pulse-red { 0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); } 50% { box-shadow: 0 0 0 8px rgba(239,68,68,0); } }
        .alert-pulse { animation: pulse-red 2s infinite; }
    </style>
    <script>setInterval(() => { window.location.reload(); }, 15000);</script>
</head>
<body class="font-sans min-h-screen">

    <header class="bg-gradient-to-r from-red-900 to-red-800 text-white px-4 py-4 flex justify-between items-center shadow-lg">
        <div class="flex items-center gap-3">
            <a href="admin_panel.php" class="text-red-300 hover:text-white text-sm">← Dashboard</a>
            <h1 class="text-xl font-bold">🚨 Monitoreo de Alertas SOS</h1>
        </div>
        <div class="flex gap-4 text-sm">
            <span class="bg-yellow-600/50 px-3 py-1 rounded-full">⏳ <?php echo $totalPendientes; ?> pendientes</span>
            <span class="bg-green-600/50 px-3 py-1 rounded-full">✅ <?php echo $totalAtendidas; ?> atendidas</span>
        </div>
    </header>

    <main class="p-4 md:p-8 max-w-5xl mx-auto pb-24">

        <?php if ($mensaje): ?>
            <div class="bg-green-600/80 p-3 rounded-lg mb-4 text-white text-center font-bold"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="flex gap-3 mb-6">
            <a href="admin_alertas.php?estado=todos" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filtroEstado==='todos'?'bg-indigo-600 text-white':'bg-gray-800 text-gray-400'; ?>">Todas</a>
            <a href="admin_alertas.php?estado=pendiente" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filtroEstado==='pendiente'?'bg-yellow-600 text-white':'bg-gray-800 text-gray-400'; ?>">Pendientes</a>
            <a href="admin_alertas.php?estado=atendida" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo $filtroEstado==='atendida'?'bg-green-600 text-white':'bg-gray-800 text-gray-400'; ?>">Atendidas</a>
        </div>

        <!-- Alertas -->
        <?php if (empty($alerts)): ?>
            <div class="card p-8 rounded-xl text-center">
                <p class="text-4xl mb-4">✅</p>
                <p class="text-gray-400 text-lg">No hay alertas SOS registradas.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($alerts as $alert): ?>
                    <div class="card p-5 rounded-xl <?php echo $alert['status']==='pendiente'?'border-l-4 border-red-500 alert-pulse':'border-l-4 border-green-500'; ?>">
                        <div class="flex flex-col md:flex-row justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-red-400 font-bold text-lg">Alerta #<?php echo $alert['id']; ?></span>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold <?php
                                        echo $alert['status']==='pendiente'?'bg-yellow-600/30 text-yellow-400':'';
                                        echo $alert['status']==='atendida'?'bg-green-600/30 text-green-400':'';
                                        echo $alert['status']==='falsa_alarma'?'bg-gray-600/30 text-gray-400':'';
                                    ?>">
                                        <?php echo ucfirst(str_replace('_',' ',$alert['status'])); ?>
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                    <p><strong class="text-gray-400">Pasajero:</strong> <?php echo htmlspecialchars($alert['usuario']); ?></p>
                                    <p><strong class="text-gray-400">Telefono:</strong> <?php echo htmlspecialchars($alert['telefono']); ?></p>
                                    <p><strong class="text-gray-400">Conductor:</strong> <?php echo htmlspecialchars($alert['conductor_name'] ?? 'No asignado'); ?></p>
                                    <p><strong class="text-gray-400">GPS:</strong> <?php echo $alert['lat']; ?>, <?php echo $alert['lng']; ?></p>
                                    <p><strong class="text-gray-400">Ruta:</strong> <?php echo htmlspecialchars($alert['pickup_address']); ?> → <?php echo htmlspecialchars($alert['destination_address']); ?></p>
                                    <p><strong class="text-gray-400">Fecha:</strong> <?php echo $alert['created_at']; ?></p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 md:items-end">
                                <a href="https://maps.google.com/?q=<?php echo $alert['lat']; ?>,<?php echo $alert['lng']; ?>" target="_blank"
                                   class="bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-700 text-center">
                                    📍 Ver en Mapa
                                </a>
                                <?php if ($alert['status'] === 'pendiente'): ?>
                                    <form id="attend-<?php echo $alert['id']; ?>" method="POST" class="inline">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                        <input type="hidden" name="new_status" value="atendida">
                                        <button type="button" onclick="confirmAttend(<?php echo $alert['id']; ?>)" class="bg-green-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-green-700 w-full">
                                            ✅ Marcar Atendida
                                        </button>
                                    </form>
                                    <form id="false-<?php echo $alert['id']; ?>" method="POST" class="inline">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                        <input type="hidden" name="new_status" value="falsa_alarma">
                                        <button type="button" onclick="confirmFalseAlarm(<?php echo $alert['id']; ?>)" class="bg-gray-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-gray-700 w-full">
                                            ❌ Falsa Alarma
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Mobile nav -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-700 flex justify-around py-2 z-50">
        <a href="admin_panel.php" class="text-center text-xs text-gray-400 p-2">📊<br>Inicio</a>
        <a href="admin_usuarios.php" class="text-center text-xs text-gray-400 p-2">👥<br>Usuarios</a>
        <a href="admin_alertas.php" class="text-center text-xs text-red-400 p-2">🚨<br>SOS</a>
        <a href="admin_tarifas.php" class="text-center text-xs text-gray-400 p-2">💰<br>Tarifas</a>
    </div>

    <script src="modal.js"></script>
    <script>
        function confirmAttend(alertId) {
            RideIt.confirm({
                title: 'Marcar como atendida',
                message: '¿Confirmas que la alerta SOS #' + alertId + ' ha sido atendida correctamente?',
                type: 'success',
                confirmText: 'Confirmar',
                confirmClass: 'btn-success',
                onConfirm: () => document.getElementById('attend-' + alertId).submit()
            });
        }

        function confirmFalseAlarm(alertId) {
            RideIt.confirm({
                title: 'Marcar como falsa alarma',
                message: '¿Estas seguro de que la alerta #' + alertId + ' es una falsa alarma?',
                type: 'warning',
                confirmText: 'Si, es falsa alarma',
                onConfirm: () => document.getElementById('false-' + alertId).submit()
            });
        }
    </script>
</body>
</html>
