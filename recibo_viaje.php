<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

$userId = $_SESSION['user_id'];
$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

// Verificar que el viaje existe y pertenece al usuario
$stmt = $conn->prepare("
    SELECT t.id, t.pickup_address, t.destination_address, t.distance, t.fare,
           t.service_type, t.payment_method, t.payment_status, t.completed_at,
           t.created_at, t.driver_id, d.name AS driver_name
    FROM trips t
    LEFT JOIN users d ON t.driver_id = d.id
    WHERE t.id = ? AND t.passenger_id = ? AND t.status = 'completado'
");
$stmt->bind_param("ii", $tripId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$trip = $result->fetch_assoc();

if (!$trip) {
    header("Location: dashboardpa.php");
    exit;
}

// Obtener nombre del pasajero
$stmtUser = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$userRow = $stmtUser->get_result()->fetch_assoc();
$passengerName = $userRow['name'] ?? 'Pasajero';

// Generar folio: RI-YYYYMMDD-XXXX
$completedDate = $trip['completed_at'] ? date('Ymd', strtotime($trip['completed_at'])) : date('Ymd');
$folio = 'RI-' . $completedDate . '-' . str_pad($trip['id'], 4, '0', STR_PAD_LEFT);

$paymentLabels = [
    'efectivo' => 'Efectivo',
    'tarjeta_debito' => 'Tarjeta de Debito',
    'tarjeta_credito' => 'Tarjeta de Credito'
];
$serviceLabels = [
    'economico' => 'Economico',
    'confort' => 'Confort'
];

$isPaid = $trip['payment_status'] === 'pagado';
$fromHistory = isset($_GET['from']) && $_GET['from'] === 'history';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Viaje - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <link rel="stylesheet" href="modal.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
        .receipt {
            background: #ffffff;
            color: #1e293b;
            border-radius: 1rem;
            max-width: 420px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        .receipt::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(to right, #3b82f6, #6366f1, #8b5cf6);
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .receipt-row:last-child { border-bottom: none; }
        .receipt-total {
            background: #f0fdf4;
            border: 2px solid #22c55e;
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
        }
        .folio-badge {
            background: #eef2ff;
            color: #4f46e5;
            font-family: monospace;
            font-weight: bold;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            display: inline-block;
        }
        .stamp-paid {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 4rem;
            font-weight: 900;
            color: rgba(34, 197, 94, 0.12);
            text-transform: uppercase;
            pointer-events: none;
            letter-spacing: 8px;
        }
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-center items-center text-gray-300 p-4">

    <div class="receipt p-6 shadow-2xl relative">
        <?php if ($isPaid): ?>
            <div class="stamp-paid">Pagado</div>
        <?php endif; ?>

        <!-- Header -->
        <div class="text-center mb-6">
            <h1 class="text-2xl font-extrabold text-gray-900">RideIt</h1>
            <p class="text-gray-500 text-sm">Comprobante de Viaje</p>
            <div class="mt-3">
                <span class="folio-badge"><?php echo htmlspecialchars($folio); ?></span>
            </div>
        </div>

        <!-- Datos del recibo -->
        <div class="space-y-1 mb-4">
            <div class="receipt-row">
                <span class="text-gray-500 text-sm">Folio</span>
                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($folio); ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-gray-500 text-sm">ID Viaje</span>
                <span class="font-bold text-gray-800">#<?php echo $trip['id']; ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-gray-500 text-sm">Fecha</span>
                <span class="font-semibold text-gray-800"><?php echo $trip['completed_at'] ? date('d/m/Y H:i', strtotime($trip['completed_at'])) : 'N/A'; ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-gray-500 text-sm">Pasajero</span>
                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($passengerName); ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-gray-500 text-sm">Conductor</span>
                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($trip['driver_name'] ?? 'No asignado'); ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-gray-500 text-sm">Servicio</span>
                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($serviceLabels[$trip['service_type']] ?? 'Economico'); ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-gray-500 text-sm">Origen</span>
                <span class="font-semibold text-gray-800 text-right text-xs max-w-xs"><?php echo htmlspecialchars($trip['pickup_address']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-gray-500 text-sm">Destino</span>
                <span class="font-semibold text-gray-800 text-right text-xs max-w-xs"><?php echo htmlspecialchars($trip['destination_address']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="text-gray-500 text-sm">Distancia</span>
                <span class="font-semibold text-gray-800"><?php echo number_format($trip['distance'], 2); ?> km</span>
            </div>
            <div class="receipt-row">
                <span class="text-gray-500 text-sm">Metodo de Pago</span>
                <span class="font-bold text-indigo-600"><?php echo htmlspecialchars($paymentLabels[$trip['payment_method']] ?? 'Efectivo'); ?></span>
            </div>
        </div>

        <!-- Total -->
        <div class="receipt-total mb-4">
            <p class="text-gray-500 text-sm">Monto Total</p>
            <p class="text-3xl font-extrabold text-green-600">$<?php echo number_format($trip['fare'], 2); ?></p>
            <?php if ($isPaid): ?>
                <span class="inline-block mt-1 bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full">PAGADO</span>
            <?php else: ?>
                <span class="inline-block mt-1 bg-yellow-100 text-yellow-700 text-xs font-bold px-3 py-1 rounded-full">PENDIENTE</span>
            <?php endif; ?>
        </div>

        <!-- Footer del recibo -->
        <div class="text-center text-gray-400 text-xs mb-4">
            <p>Gracias por viajar con RideIt</p>
            <p>Este comprobante es valido como recibo de pago.</p>
        </div>

        <!-- Acciones -->
        <div class="space-y-3 no-print">
            <?php if (!$fromHistory && $trip['payment_status'] === 'pagado'): ?>
                <a href="calificar_viaje.php?trip_id=<?php echo $tripId; ?>" class="block w-full bg-gradient-to-r from-yellow-500 to-yellow-400 text-yellow-900 py-3 rounded-xl font-bold shadow-lg hover:scale-105 transition text-center text-lg">
                    Calificar viaje
                </a>
            <?php endif; ?>

            <?php if ($fromHistory): ?>
                <a href="historial_pasajero.php" class="block w-full bg-gradient-to-r from-blue-600 to-blue-500 text-white py-3 rounded-xl font-bold shadow-lg hover:scale-105 transition text-center">
                    Volver al historial
                </a>
            <?php else: ?>
                <a href="dashboardpa.php" class="block w-full bg-gray-700 text-white py-3 rounded-xl font-bold shadow-lg hover:bg-gray-600 transition text-center">
                    Ir al inicio
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="modal.js"></script>
</body>
</html>
