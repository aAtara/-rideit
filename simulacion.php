<?php
include 'db.php';
include 'csrf.php';
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

$userId = $_SESSION['user_id'];
$mensaje = "";
$error = "";

// Obtener viajes pendientes/asignados del usuario
$stmt = $conn->prepare("
    SELECT t.id, t.pickup_address, t.destination_address, t.distance, t.fare, t.status,
           d.name AS driver_name, d.plate AS driver_plate, d.phone AS driver_phone
    FROM trips t
    LEFT JOIN users d ON t.driver_id = d.id
    WHERE t.passenger_id = ? AND t.status IN ('pendiente', 'asignado', 'en_destino', 'afuera')
    ORDER BY t.created_at DESC LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$activeTrips = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener conductores disponibles
$drivers = [];
$res = $conn->query("SELECT id, name, plate, phone FROM users WHERE role = 'conductor' LIMIT 10");
if ($res) $drivers = $res->fetch_all(MYSQLI_ASSOC);

// Accion: Simular asignacion de conductor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCsrfToken()) {
        $error = "Token invalido.";
    } else {
        $action = $_POST['action'];
        $tripId = isset($_POST['trip_id']) ? (int)$_POST['trip_id'] : 0;

        if ($action === 'assign_driver' && $tripId > 0) {
            $driverId = (int)$_POST['driver_id'];
            $stmt = $conn->prepare("UPDATE trips SET status = 'asignado', driver_id = ? WHERE id = ? AND passenger_id = ?");
            $stmt->bind_param("iii", $driverId, $tripId, $userId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $mensaje = "Conductor asignado al viaje #$tripId.";
            } else {
                $error = "No se pudo asignar el conductor.";
            }
        }

        if ($action === 'change_status' && $tripId > 0) {
            $newStatus = $_POST['new_status'];
            $allowed = ['asignado', 'en_destino', 'afuera', 'completado'];
            if (in_array($newStatus, $allowed)) {
                if ($newStatus === 'completado') {
                    $stmt = $conn->prepare("UPDATE trips SET status = ?, completed_at = NOW() WHERE id = ?");
                } else {
                    $stmt = $conn->prepare("UPDATE trips SET status = ? WHERE id = ?");
                }
                $stmt->bind_param("si", $newStatus, $tripId);
                if ($stmt->execute()) {
                    $mensaje = "Viaje #$tripId actualizado a: $newStatus";
                }
            }
        }

        if ($action === 'create_test_trip') {
            $pickup = "Av. Tecnologico 80, Cd. Delicias, Chihuahua";
            $destination = "Plaza Encanto, Delicias, Chihuahua";
            $distance = 3.5;
            $tarifaBase = defined('TARIFA_BASE') ? TARIFA_BASE : 15;
            $tarifaPorKm = defined('TARIFA_POR_KM') ? TARIFA_POR_KM : 10;
            $fare = $tarifaBase + ($tarifaPorKm * $distance);
            $stmt = $conn->prepare("INSERT INTO trips (passenger_id, pickup_address, destination_address, distance, fare, status) VALUES (?, ?, ?, ?, ?, 'pendiente')");
            $stmt->bind_param("issdd", $userId, $pickup, $destination, $distance, $fare);
            if ($stmt->execute()) {
                $mensaje = "Viaje de prueba creado (ID: " . $conn->insert_id . "). Tarifa: $" . number_format($fare, 2);
            }
        }

        // Recargar viajes
        header("Location: simulacion.php" . ($mensaje ? "?msg=" . urlencode($mensaje) : "") . ($error ? "?err=" . urlencode($error) : ""));
        exit;
    }
}

if (isset($_GET['msg'])) $mensaje = $_GET['msg'];
if (isset($_GET['err'])) $error = $_GET['err'];

$statusLabels = [
    'pendiente' => ['Pendiente', 'text-yellow-400', 'Esperando conductor...'],
    'asignado' => ['Asignado', 'text-blue-400', 'Conductor en camino al punto de recogida'],
    'en_destino' => ['En destino', 'text-purple-400', 'Conductor llego, viaje en curso'],
    'afuera' => ['Afuera', 'text-orange-400', 'Conductor esta afuera esperando'],
    'completado' => ['Completado', 'text-green-400', 'Viaje finalizado'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulacion de Viaje - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
        .glass { background: rgba(23, 37, 84, 0.72); backdrop-filter: blur(5px); border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.10); }
        select, input { background-color: #1e293b; color: #f8fafc; border: 1px solid #475569; border-radius: 8px; padding: 0.5rem; }
        .status-flow { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .status-step { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: bold; }
        .step-active { background: #3b82f6; color: white; }
        .step-done { background: #22c55e; color: white; }
        .step-pending { background: #374151; color: #9ca3af; }
        .arrow { color: #4b5563; }
    </style>
</head>
<body class="min-h-screen text-gray-300">

    <header class="bg-gradient-to-r from-indigo-800 to-purple-900 text-white px-4 py-3 flex justify-between items-center shadow-lg">
        <h1 class="text-xl font-bold">🧪 Simulacion de Viaje Completo</h1>
        <div class="flex gap-2">
            <a href="dashboardpa.php" class="bg-white text-indigo-600 px-4 py-2 rounded-lg text-sm font-bold">Dashboard</a>
            <a href="admin_sos.php" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-bold">Ver SOS</a>
        </div>
    </header>

    <main class="p-4 max-w-3xl mx-auto space-y-6">

        <?php if ($mensaje): ?>
            <div class="bg-green-500/90 text-center p-3 rounded-lg text-white shadow-md"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-500/90 text-center p-3 rounded-lg text-white shadow-md"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Guia de flujo -->
        <div class="glass p-4">
            <h2 class="text-lg font-bold text-white mb-2">Flujo del viaje (Casos de Prueba)</h2>
            <div class="status-flow">
                <span class="status-step step-done">1. Registro (RF02)</span>
                <span class="arrow">→</span>
                <span class="status-step step-done">2. Login (RF01)</span>
                <span class="arrow">→</span>
                <span class="status-step step-done">3. Solicitar viaje (RF04)</span>
                <span class="arrow">→</span>
                <span class="status-step step-done">4. Tarifa/ETA (RF05)</span>
                <span class="arrow">→</span>
                <span class="status-step step-active">5. Asignar conductor (RF06)</span>
                <span class="arrow">→</span>
                <span class="status-step step-pending">6. Tracking (RF07)</span>
                <span class="arrow">→</span>
                <span class="status-step step-pending">7. Completar (RF09)</span>
                <span class="arrow">→</span>
                <span class="status-step step-pending">8. Calificar (RF10)</span>
            </div>
        </div>

        <!-- Crear viaje de prueba -->
        <div class="glass p-4">
            <h2 class="text-lg font-bold text-white mb-3">Paso 1: Crear viaje de prueba</h2>
            <p class="text-sm text-gray-400 mb-3">Crea un viaje automatico de Tec de Delicias a Plaza Encanto (o usa uberx.php para uno real)</p>
            <div class="flex gap-3">
                <form action="simulacion.php" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="create_test_trip">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-600 transition">
                        Crear viaje de prueba
                    </button>
                </form>
                <a href="uberx.php" class="bg-indigo-500 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-600 transition">
                    Solicitar viaje real (mapa)
                </a>
            </div>
        </div>

        <!-- Viajes activos -->
        <?php if (!empty($activeTrips)): ?>
        <div class="glass p-4 space-y-4">
            <h2 class="text-lg font-bold text-white">Viajes activos</h2>

            <?php foreach ($activeTrips as $trip): ?>
                <?php
                    $st = $trip['status'];
                    $label = $statusLabels[$st] ?? ['Desconocido', 'text-gray-400', ''];
                    $statusOrder = ['pendiente' => 1, 'asignado' => 2, 'en_destino' => 3, 'afuera' => 3, 'completado' => 4];
                    $currentStep = $statusOrder[$st] ?? 0;
                ?>
                <div class="bg-gray-800/80 p-4 rounded-xl border-l-4 <?php echo $st === 'pendiente' ? 'border-yellow-500' : ($st === 'completado' ? 'border-green-500' : 'border-blue-500'); ?>">

                    <!-- Info del viaje -->
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="font-bold text-white">Viaje #<?php echo $trip['id']; ?></p>
                            <p class="text-sm">📍 <?php echo htmlspecialchars($trip['pickup_address']); ?></p>
                            <p class="text-sm">🏁 <?php echo htmlspecialchars($trip['destination_address']); ?></p>
                            <p class="text-sm">📏 <?php echo $trip['distance']; ?> km | 💰 $<?php echo number_format($trip['fare'], 2); ?></p>
                        </div>
                        <span class="<?php echo $label[1]; ?> font-bold text-sm"><?php echo $label[0]; ?></span>
                    </div>

                    <!-- Barra de progreso visual -->
                    <div class="status-flow mb-3">
                        <span class="status-step <?php echo $currentStep >= 1 ? ($currentStep > 1 ? 'step-done' : 'step-active') : 'step-pending'; ?>">Pendiente</span>
                        <span class="arrow">→</span>
                        <span class="status-step <?php echo $currentStep >= 2 ? ($currentStep > 2 ? 'step-done' : 'step-active') : 'step-pending'; ?>">Asignado</span>
                        <span class="arrow">→</span>
                        <span class="status-step <?php echo $currentStep >= 3 ? ($currentStep > 3 ? 'step-done' : 'step-active') : 'step-pending'; ?>">En curso</span>
                        <span class="arrow">→</span>
                        <span class="status-step <?php echo $currentStep >= 4 ? 'step-done' : 'step-pending'; ?>">Completado</span>
                    </div>

                    <!-- Info del conductor -->
                    <?php if ($trip['driver_name']): ?>
                        <div class="bg-gray-700/50 p-2 rounded-lg mb-3">
                            <p class="text-sm"><strong>Conductor:</strong> <?php echo htmlspecialchars($trip['driver_name']); ?></p>
                            <p class="text-sm"><strong>Placas:</strong> <?php echo htmlspecialchars($trip['driver_plate'] ?? 'N/A'); ?></p>
                            <p class="text-sm"><strong>Telefono:</strong> <?php echo htmlspecialchars($trip['driver_phone'] ?? 'N/A'); ?></p>
                        </div>
                    <?php endif; ?>

                    <p class="text-xs text-gray-400 mb-3 italic"><?php echo $label[2]; ?></p>

                    <!-- Acciones segun estado -->
                    <div class="flex flex-wrap gap-2">
                        <?php if ($st === 'pendiente'): ?>
                            <!-- Asignar conductor -->
                            <form action="simulacion.php" method="POST" class="flex gap-2 items-center">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="assign_driver">
                                <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                <select name="driver_id" required class="text-sm">
                                    <option value="">Seleccionar conductor</option>
                                    <?php foreach ($drivers as $d): ?>
                                        <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['plate'] ?? 'Sin placa'); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-600">
                                    Asignar conductor
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($st === 'asignado'): ?>
                            <form action="simulacion.php" method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="change_status">
                                <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                <input type="hidden" name="new_status" value="afuera">
                                <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-orange-600">
                                    Simular: Conductor llego (afuera)
                                </button>
                            </form>
                            <a href="trackingpa.php?trip_id=<?php echo $trip['id']; ?>" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-600">
                                Ver tracking en mapa
                            </a>
                        <?php endif; ?>

                        <?php if ($st === 'afuera'): ?>
                            <form action="simulacion.php" method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="change_status">
                                <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                <input type="hidden" name="new_status" value="en_destino">
                                <button type="submit" class="bg-purple-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-purple-600">
                                    Simular: Iniciar viaje
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($st === 'en_destino'): ?>
                            <form action="simulacion.php" method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="change_status">
                                <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                <input type="hidden" name="new_status" value="completado">
                                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-600">
                                    Simular: Viaje completado
                                </button>
                            </form>
                            <a href="trackingpa.php?trip_id=<?php echo $trip['id']; ?>" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-600">
                                Ver tracking + SOS
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="glass p-6 text-center">
                <p class="text-gray-400">No hay viajes activos. Crea uno arriba para comenzar la simulacion.</p>
            </div>
        <?php endif; ?>

        <!-- Links a todas las vistas -->
        <div class="glass p-4">
            <h2 class="text-lg font-bold text-white mb-3">Todas las vistas (Casos de Prueba)</h2>
            <div class="grid grid-cols-2 gap-2">
                <a href="login_pasajero.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-blue-300">RF01</strong> - Login Pasajero
                </a>
                <a href="login_conductor.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-blue-300">RF01</strong> - Login Conductor
                </a>
                <a href="register_pasajero.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-green-300">RF02</strong> - Registro Pasajero
                </a>
                <a href="registrocon.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-green-300">RF02</strong> - Registro Conductor
                </a>
                <a href="profilepa.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-yellow-300">RF03</strong> - Perfil Pasajero
                </a>
                <a href="uberx.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-purple-300">RF04/RF05</strong> - Solicitar viaje + Tarifa/ETA
                </a>
                <a href="historial_pasajero.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-indigo-300">RF12</strong> - Historial de viajes
                </a>
                <a href="help.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-pink-300">RF13</strong> - Ayuda y FAQ
                </a>
                <a href="privacidad.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-teal-300">RNF05</strong> - Aviso de Privacidad
                </a>
                <a href="terminos.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-teal-300">RNF05</strong> - Terminos y Condiciones
                </a>
                <a href="admin_sos.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-red-300">RF08</strong> - Ver alertas SOS
                </a>
                <a href="dashboardpa.php" class="bg-gray-700 p-3 rounded-lg text-sm hover:bg-gray-600 transition">
                    <strong class="text-blue-300">Panel</strong> - Dashboard Pasajero
                </a>
            </div>
        </div>

        <!-- Guia para la docente -->
        <div class="glass p-4">
            <h2 class="text-lg font-bold text-white mb-2">Guia de presentacion para la docente</h2>
            <ol class="text-sm space-y-2 list-decimal list-inside">
                <li><strong>RF01 - Login:</strong> Ir a login_pasajero.php, ingresar credenciales, ver que redirige al dashboard con nombre.</li>
                <li><strong>RF02 - Registro:</strong> Ir a register_pasajero.php, mostrar validaciones (email, telefono, contrasena 8+ chars).</li>
                <li><strong>RF03 - Perfil:</strong> Ir a profilepa.php, cambiar telefono, agregar direccion.</li>
                <li><strong>RF04 - Solicitar viaje:</strong> Ir a uberx.php, mover marcador, escribir destino con autocompletado.</li>
                <li><strong>RF05 - Tarifa/ETA:</strong> En uberx.php ver tarifa, distancia y tiempo estimado calculados.</li>
                <li><strong>RF06 - Asignar conductor:</strong> En simulacion.php, asignar un conductor al viaje pendiente.</li>
                <li><strong>RF07 - Tracking:</strong> Hacer clic en "Ver tracking en mapa" para ver el seguimiento GPS.</li>
                <li><strong>RF08 - SOS:</strong> En trackingpa.php presionar boton SOS, confirmar, ver alerta en admin_sos.php.</li>
                <li><strong>RF09 - Pago:</strong> Al completar viaje, la tarifa queda registrada (pago en efectivo MVP).</li>
                <li><strong>RF10 - Calificar:</strong> Despues de completar, ir a dashboardpa.php y dar clic en "Calificar viaje".</li>
                <li><strong>RF12 - Historial:</strong> Ir a historial_pasajero.php para ver todos los viajes.</li>
                <li><strong>RF13 - Ayuda:</strong> Ir a help.php, buscar en FAQ, enviar reporte de soporte.</li>
                <li><strong>RNF04 - Seguridad:</strong> Mostrar HTTPS en barra, contrasena con puntos, CSRF en formularios.</li>
                <li><strong>RNF05 - Privacidad:</strong> Mostrar aviso de privacidad y permiso de ubicacion del navegador.</li>
                <li><strong>RNF07 - Codigo:</strong> Mostrar README.md en GitHub con estructura del proyecto.</li>
                <li><strong>RF01 - Logout:</strong> Cerrar sesion y mostrar que no entra sin credenciales.</li>
            </ol>
        </div>
    </main>
</body>
</html>
