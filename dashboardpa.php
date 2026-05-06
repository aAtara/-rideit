<?php
// SOLO EN PRUEBAS: mostrar errores PHP
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

include 'db.php';
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

// Obtener el nombre del usuario desde la sesión
$userName = $_SESSION['user_name'];
$userId = $_SESSION['user_id'];

// Determinar el saludo según la hora
date_default_timezone_set('America/Mexico_City');
$hour = date("H");
if ($hour >= 0 && $hour < 6) {
    $greeting = "Buenas madrugadas";
} elseif ($hour >= 6 && $hour < 12) {
    $greeting = "Buenos días";
} elseif ($hour >= 12 && $hour < 19) {
    $greeting = "Buenas tardes";
} else {
    $greeting = "Buenas noches";
}

// Mensaje flash para bienvenida o acciones recientes
$mensaje = "";
if (isset($_GET['bienvenido'])) {
    $mensaje = "¡Registro exitoso! Bienvenido a RideIt, $userName.";
}
if (isset($_GET['cambio'])) {
    $mensaje = "¡Contraseña actualizada correctamente!";
}

// OBTENER ESTADÍSTICAS RÁPIDAS
$totalViajes = 0;
$totalGastado = 0;
$viajesPendientes = 0;
$calificacionPromedio = null;

// Consulta viajes totales y total gastado
$stmt = $conn->prepare("SELECT COUNT(*) AS total, IFNULL(SUM(fare),0) AS gastado FROM trips WHERE passenger_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $totalViajes = $row['total'] ?? 0;
        $totalGastado = $row['gastado'] ?? 0;
    }
    $stmt->close();
}

// Viajes pendientes
$stmt = $conn->prepare("SELECT COUNT(*) AS pendientes FROM trips WHERE passenger_id = ? AND status = 'pendiente'");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $viajesPendientes = $row['pendientes'] ?? 0;
    }
    $stmt->close();
}

// Calificación promedio (si la columna existe)
$hasRating = false;
$colCheck = $conn->query("SHOW COLUMNS FROM trips LIKE 'rating'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasRating = true;
    $stmt = $conn->prepare("SELECT AVG(rating) AS promedio FROM trips WHERE passenger_id = ? AND rating IS NOT NULL");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc() && !is_null($row['promedio'])) {
            $calificacionPromedio = round($row['promedio'],1);
        }
        $stmt->close();
    }
}

// Obtener los 4 pedidos recientes del pasajero
$recentTrips = [];
if ($hasRating) {
    $stmt = $conn->prepare("
        SELECT id, destination_address, fare, status, rating, DATE_FORMAT(completed_at, '%d/%m/%Y') AS completed_date 
        FROM trips 
        WHERE passenger_id = ? 
        ORDER BY completed_at DESC 
        LIMIT 4
    ");
} else {
    $stmt = $conn->prepare("
        SELECT id, destination_address, fare, status, DATE_FORMAT(completed_at, '%d/%m/%Y') AS completed_date 
        FROM trips 
        WHERE passenger_id = ? 
        ORDER BY completed_at DESC 
        LIMIT 4
    ");
}
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $recentTrips = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// FOTO DE PERFIL Y DATOS EXTRA (si la columna existe)
$profilePhoto = "";
$colPhoto = $conn->query("SHOW COLUMNS FROM users LIKE 'photo'");
if ($colPhoto && $colPhoto->num_rows > 0) {
    $stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $profilePhoto = $row['photo'] ?? '';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RideIt Pasajeros</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #01579B 0%, #003366 100%);
        }
        .dashboard-header {
            background: rgba(23, 37, 84, 0.95);
            backdrop-filter: blur(5px);
        }
        .card {
            background: rgba(255, 255, 255, 0.08);
            color: white;
        }
        .card:hover {
            transform: scale(1.04);
            transition: 0.25s cubic-bezier(.4,2,.4,1);
        }
        .btn-logout {
            background: rgba(231, 76, 60, 0.9);
        }
        .btn-logout:hover {
            background: rgba(192, 57, 43, 0.9);
        }
        .perfil-pic {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 9999px;
            border: 2px solid #29B6F6;
            background: #011c33;
        }
        .animate-fade-in { animation: fadeIn 0.9s; }
        @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }
    </style>
</head>
<body class="font-sans text-gray-300">

    <!-- Contenedor principal -->
    <div class="flex flex-col min-h-screen">

        <!-- Barra de navegación superior -->
        <header class="dashboard-header text-white px-4 py-3 flex justify-between items-center shadow-lg">
            <div class="flex items-center gap-3">
                <?php if ($profilePhoto): ?>
                    <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="Perfil" class="perfil-pic">
                <?php else: ?>
                    <div class="perfil-pic flex items-center justify-center text-2xl bg-blue-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="white"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a8 8 0 00-8 8h16a8 8 0 00-8-8z"/></svg>
                    </div>
                <?php endif; ?>
                <h1 class="text-lg font-bold">🚖 RideIt - Pasajeros</h1>
            </div>
            <div class="flex space-x-3">
                <a href="help.php" class="bg-blue-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 transition">
                    Ayuda
                </a>
                <a href="profilepa.php" class="bg-indigo-500 px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-600 transition">
                    Perfil
                </a>
                <form action="logoutpa.php" method="POST" class="inline">
                    <button type="submit" class="btn-logout px-4 py-2 rounded-lg text-sm font-bold hover:shadow-md transition">
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </header>

        <!-- Contenido principal -->
        <main class="flex-1 overflow-y-auto p-4">

            <!-- Mensaje flash -->
            <?php if ($mensaje): ?>
            <div class="bg-green-500/90 text-center p-3 rounded-lg mb-4 text-white shadow-md w-full max-w-lg mx-auto animate-fade-in">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
            <?php endif; ?>

            <!-- Saludo dinámico -->
            <section class="mb-6 text-center animate-fade-in">
                <h2 class="text-2xl font-bold text-white flex items-center justify-center gap-2">
                    <?php echo "$greeting, " . htmlspecialchars($userName) . "!"; ?>
                    <?php if (!is_null($calificacionPromedio)): ?>
                        <span class="ml-2 bg-yellow-400/90 text-yellow-900 text-xs px-3 py-1 rounded-full font-bold flex items-center gap-1">
                            ★ <?php echo $calificacionPromedio; ?>
                        </span>
                    <?php endif; ?>
                </h2>
                <p class="text-sm text-blue-200">¿Qué puedo hacer por ti hoy?</p>
            </section>

            <!-- Resumen rápido -->
            <section class="flex gap-4 justify-center mb-6 flex-wrap animate-fade-in">
                <div class="card p-4 rounded-lg flex flex-col items-center min-w-[110px]">
                    <span class="text-2xl font-bold text-blue-300"><?php echo $totalViajes; ?></span>
                    <span class="text-xs text-gray-300">Viajes totales</span>
                </div>
                <div class="card p-4 rounded-lg flex flex-col items-center min-w-[110px]">
                    <span class="text-2xl font-bold text-green-300">$<?php echo number_format($totalGastado, 2); ?></span>
                    <span class="text-xs text-gray-300">Total gastado</span>
                </div>
                <div class="card p-4 rounded-lg flex flex-col items-center min-w-[110px]">
                    <span class="text-2xl font-bold text-yellow-300"><?php echo $viajesPendientes; ?></span>
                    <span class="text-xs text-gray-300">Pendientes</span>
                </div>
            </section>

            <!-- Opciones -->
            <h2 class="text-xl font-bold mb-4 text-gray-200">Opciones</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10 animate-fade-in">
                <!-- Solicitar Viaje -->
                <a href="uberx.php" class="card p-4 rounded-lg shadow-md hover:shadow-xl transition flex flex-col items-center gap-2">
                    <span class="text-4xl">🚗</span>
                    <h3 class="text-lg font-bold text-indigo-400">Solicitar un Viaje</h3>
                    <p class="text-sm text-gray-300">Viaja rápido y seguro con UberX.</p>
                </a>
                <!-- Pedir un Favor -->
                <a href="uberfavor.php" class="card p-4 rounded-lg shadow-md hover:shadow-xl transition flex flex-col items-center gap-2">
                    <span class="text-4xl">🎁</span>
                    <h3 class="text-lg font-bold text-indigo-400">Pedir un Favor</h3>
                    <p class="text-sm text-gray-300">Solicita ayuda para tus recados.</p>
                </a>
                <!-- Comprar en Tiendas -->
                <a href="ubermark.php" class="card p-4 rounded-lg shadow-md hover:shadow-xl transition flex flex-col items-center gap-2">
                    <span class="text-4xl">🛒</span>
                    <h3 class="text-lg font-bold text-indigo-400">Comprar en Tiendas</h3>
                    <p class="text-sm text-gray-300">Compra en tus tiendas favoritas y recibe tus productos.</p>
                </a>
            </div>

            <!-- Viajes Recientes -->
            <h2 class="text-xl font-bold mt-8 mb-4 text-gray-200">Viajes Recientes</h2>
            <div class="space-y-4 animate-fade-in">
                <?php if (!empty($recentTrips)): ?>
                    <?php foreach ($recentTrips as $trip): ?>
                        <div class="card p-4 rounded-lg shadow-md flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-indigo-400">
                                    Viaje a: <?php echo htmlspecialchars($trip['destination_address'] ?? 'Sin dirección'); ?>
                                </h3>
                                <p class="text-sm text-gray-400">
                                    Fecha: <?php echo htmlspecialchars($trip['completed_date'] ?? 'Sin fecha'); ?> | Tarifa: $<?php echo number_format($trip['fare'] ?? 0, 2); ?>
                                </p>
                                <p class="text-sm font-bold <?php 
                                    echo $trip['status'] === 'completado' ? 'text-green-400' : (
                                        $trip['status'] === 'pendiente' ? 'text-yellow-400' : 'text-red-400'
                                    ); ?>">
                                    Estado: 
                                    <?php 
                                        switch ($trip['status']) {
                                            case 'completado':
                                                echo 'Completado';
                                                break;
                                            case 'pendiente':
                                                echo 'Pendiente';
                                                break;
                                            case 'rechazado':
                                                echo 'Rechazado';
                                                break;
                                            case 'asignado':
                                                echo 'Asignado';
                                                break;
                                            default:
                                                echo 'Desconocido';
                                                break;
                                        }
                                    ?>
                                </p>
                            </div>
                            <?php if ($hasRating && $trip['status'] === 'completado' && isset($trip['id'])): ?>
                                <div>
                                    <?php if (!isset($trip['rating']) || is_null($trip['rating'])): ?>
                                        <a href="calificar_viaje.php?trip_id=<?php echo urlencode($trip['id']); ?>"
                                            class="bg-yellow-400/90 text-yellow-900 px-4 py-2 rounded-lg font-bold text-xs hover:bg-yellow-400 transition shadow">
                                            Calificar viaje
                                        </a>
                                    <?php else: ?>
                                        <span class="bg-yellow-400/90 text-yellow-900 px-4 py-2 rounded-lg font-bold text-xs">
                                            Calificación: ★ <?php echo htmlspecialchars($trip['rating']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-sm text-gray-400">No tienes viajes recientes.</p>
                <?php endif; ?>
            </div>
        </main>
        <footer class="bg-black text-gray-300 text-center py-6 mt-8 w-full text-xs">
            <div>
                © 2025 RideIt. Todos los derechos reservados.
                <span class="ml-2">| v1.0</span>
            </div>
        </footer>
    </div>
    <script>
        // Fade in animationa
        document.querySelectorAll('.animate-fade-in').forEach(function(el, i) {
            el.style.opacity = 0;
            setTimeout(() => {
                el.style.transition = "opacity 0.9s";
                el.style.opacity = 1;
            }, 100 + i * 150);
        });
    </script>
</body>
</html>