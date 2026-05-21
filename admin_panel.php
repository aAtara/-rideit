<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login_admin.php");
    exit;
}

$adminName = $_SESSION['user_name'];

// Estadisticas generales
$totalPasajeros = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='pasajero'")->fetch_assoc()['c'];
$totalConductores = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='conductor'")->fetch_assoc()['c'];
$totalViajes = $conn->query("SELECT COUNT(*) AS c FROM trips")->fetch_assoc()['c'];
$viajesCompletados = $conn->query("SELECT COUNT(*) AS c FROM trips WHERE status='completado'")->fetch_assoc()['c'];
$viajesPendientes = $conn->query("SELECT COUNT(*) AS c FROM trips WHERE status='pendiente'")->fetch_assoc()['c'];

$ingresosTotales = $conn->query("SELECT COALESCE(SUM(fare),0) AS t FROM trips WHERE status='completado'")->fetch_assoc()['t'];

// Alertas SOS pendientes
$sosPendientes = 0;
$tableCheck = $conn->query("SHOW TABLES LIKE 'sos_alerts'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $sosPendientes = $conn->query("SELECT COUNT(*) AS c FROM sos_alerts WHERE status='pendiente'")->fetch_assoc()['c'];
}

// Tarifas actuales
require_once 'config.php';
$tarifaBase = TARIFA_BASE;
$tarifaPorKm = TARIFA_POR_KM;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <link rel="stylesheet" href="modal.css">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .sidebar { background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%); }
        .card { background: #1e293b; border: 1px solid #334155; }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
        .nav-link { transition: all 0.2s; border-left: 3px solid transparent; }
        .nav-link:hover, .nav-link.active { background: rgba(99,102,241,0.2); border-left-color: #818cf8; }
    </style>
</head>
<body class="font-sans min-h-screen">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 p-6 flex flex-col shadow-xl hidden md:flex">
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 flex items-center justify-center text-2xl mb-2">🛡️</div>
                <h2 class="text-white font-bold text-lg">RideIt Admin</h2>
                <p class="text-indigo-300 text-xs"><?php echo htmlspecialchars($adminName); ?></p>
            </div>
            <nav class="flex-1 space-y-1">
                <a href="admin_panel.php" class="nav-link active block px-4 py-3 rounded-lg text-white text-sm font-medium">
                    📊 Dashboard
                </a>
                <a href="admin_usuarios.php" class="nav-link block px-4 py-3 rounded-lg text-gray-300 text-sm font-medium">
                    👥 Gestion de Usuarios
                </a>
                <a href="admin_alertas.php" class="nav-link block px-4 py-3 rounded-lg text-gray-300 text-sm font-medium">
                    🚨 Alertas SOS
                    <?php if ($sosPendientes > 0): ?>
                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full ml-2"><?php echo $sosPendientes; ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_tarifas.php" class="nav-link block px-4 py-3 rounded-lg text-gray-300 text-sm font-medium">
                    💰 Configurar Tarifas
                </a>
                <a href="admin_viajes.php" class="nav-link block px-4 py-3 rounded-lg text-gray-300 text-sm font-medium">
                    🗺️ Viajes
                </a>
            </nav>
            <button onclick="confirmAdminLogout()" class="mt-4 block w-full text-center bg-red-600/80 text-white py-2 rounded-lg text-sm font-bold hover:bg-red-700 transition">
                Cerrar Sesion
            </button>
        </aside>

        <!-- Mobile nav -->
        <div class="md:hidden fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-700 flex justify-around py-2 z-50">
            <a href="admin_panel.php" class="text-center text-xs text-indigo-400 p-2">📊<br>Inicio</a>
            <a href="admin_usuarios.php" class="text-center text-xs text-gray-400 p-2">👥<br>Usuarios</a>
            <a href="admin_alertas.php" class="text-center text-xs text-gray-400 p-2 relative">
                🚨<br>SOS
                <?php if ($sosPendientes > 0): ?><span class="absolute top-0 right-0 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center"><?php echo $sosPendientes; ?></span><?php endif; ?>
            </a>
            <a href="admin_tarifas.php" class="text-center text-xs text-gray-400 p-2">💰<br>Tarifas</a>
            <a href="admin_viajes.php" class="text-center text-xs text-gray-400 p-2">🗺️<br>Viajes</a>
        </div>

        <!-- Main content -->
        <main class="flex-1 p-6 md:p-8 pb-24 md:pb-8 overflow-y-auto">
            <h1 class="text-2xl font-bold text-white mb-6">Dashboard General</h1>

            <!-- Stats grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <div class="card stat-card p-4 rounded-xl text-center">
                    <p class="text-3xl font-bold text-blue-400"><?php echo $totalPasajeros; ?></p>
                    <p class="text-xs text-gray-400 mt-1">Pasajeros</p>
                </div>
                <div class="card stat-card p-4 rounded-xl text-center">
                    <p class="text-3xl font-bold text-green-400"><?php echo $totalConductores; ?></p>
                    <p class="text-xs text-gray-400 mt-1">Conductores</p>
                </div>
                <div class="card stat-card p-4 rounded-xl text-center">
                    <p class="text-3xl font-bold text-purple-400"><?php echo $totalViajes; ?></p>
                    <p class="text-xs text-gray-400 mt-1">Viajes Total</p>
                </div>
                <div class="card stat-card p-4 rounded-xl text-center">
                    <p class="text-3xl font-bold text-emerald-400"><?php echo $viajesCompletados; ?></p>
                    <p class="text-xs text-gray-400 mt-1">Completados</p>
                </div>
                <div class="card stat-card p-4 rounded-xl text-center">
                    <p class="text-3xl font-bold text-yellow-400"><?php echo $viajesPendientes; ?></p>
                    <p class="text-xs text-gray-400 mt-1">Pendientes</p>
                </div>
                <div class="card stat-card p-4 rounded-xl text-center">
                    <p class="text-3xl font-bold text-cyan-400">$<?php echo number_format($ingresosTotales, 2); ?></p>
                    <p class="text-xs text-gray-400 mt-1">Ingresos</p>
                </div>
            </div>

            <!-- Quick actions -->
            <h2 class="text-lg font-bold text-white mb-4">Acceso Rapido</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <a href="admin_usuarios.php" class="card p-5 rounded-xl hover:border-indigo-500 transition flex items-center gap-4">
                    <span class="text-3xl">👥</span>
                    <div>
                        <p class="text-white font-bold">Gestionar Usuarios</p>
                        <p class="text-xs text-gray-400">Pasajeros y conductores</p>
                    </div>
                </a>
                <a href="admin_alertas.php" class="card p-5 rounded-xl hover:border-red-500 transition flex items-center gap-4">
                    <span class="text-3xl">🚨</span>
                    <div>
                        <p class="text-white font-bold">Alertas SOS</p>
                        <p class="text-xs text-gray-400"><?php echo $sosPendientes; ?> pendientes</p>
                    </div>
                </a>
                <a href="admin_tarifas.php" class="card p-5 rounded-xl hover:border-green-500 transition flex items-center gap-4">
                    <span class="text-3xl">💰</span>
                    <div>
                        <p class="text-white font-bold">Tarifas</p>
                        <p class="text-xs text-gray-400">Base: $<?php echo $tarifaBase; ?> + $<?php echo $tarifaPorKm; ?>/km</p>
                    </div>
                </a>
                <a href="admin_viajes.php" class="card p-5 rounded-xl hover:border-purple-500 transition flex items-center gap-4">
                    <span class="text-3xl">🗺️</span>
                    <div>
                        <p class="text-white font-bold">Ver Viajes</p>
                        <p class="text-xs text-gray-400">Historial completo</p>
                    </div>
                </a>
            </div>

            <!-- Tarifas actuales -->
            <div class="card p-6 rounded-xl mb-8">
                <h2 class="text-lg font-bold text-white mb-3">Tarifas Actuales</h2>
                <div class="flex gap-8">
                    <div>
                        <p class="text-gray-400 text-sm">Tarifa Base</p>
                        <p class="text-2xl font-bold text-green-400">$<?php echo number_format($tarifaBase, 2); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Por Kilometro</p>
                        <p class="text-2xl font-bold text-green-400">$<?php echo number_format($tarifaPorKm, 2); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Ejemplo 10 km</p>
                        <p class="text-2xl font-bold text-cyan-400">$<?php echo number_format($tarifaBase + $tarifaPorKm * 10, 2); ?></p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="modal.js"></script>
    <script>
        function confirmAdminLogout() {
            RideIt.confirm({
                title: 'Cerrar sesion de administrador',
                message: '¿Deseas cerrar tu sesion de administrador?',
                type: 'warning',
                confirmText: 'Cerrar sesion',
                confirmClass: 'btn-danger',
                onConfirm: () => window.location.href = 'logout_admin.php'
            });
        }
    </script>
</body>
</html>
