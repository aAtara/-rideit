<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

$userId = $_SESSION['user_id'];
$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

// Verificar que el viaje existe, pertenece al usuario y esta completado
$stmt = $conn->prepare("
    SELECT t.id, t.pickup_address, t.destination_address, t.distance, t.fare,
           t.service_type, t.payment_method, t.payment_status, t.completed_at,
           t.driver_id, d.name AS driver_name
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

// Si ya esta pagado, redirigir al recibo
if ($trip['payment_status'] === 'pagado') {
    header("Location: recibo_viaje.php?trip_id=$tripId");
    exit;
}

$paymentLabels = [
    'efectivo' => 'Efectivo',
    'tarjeta_debito' => 'Tarjeta de Debito',
    'tarjeta_credito' => 'Tarjeta de Credito'
];
$serviceLabels = [
    'economico' => 'Economico',
    'confort' => 'Confort'
];

$error = '';

// Procesar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = "Token de seguridad invalido.";
    } else {
        // Marcar como pagado
        $stmtPay = $conn->prepare("UPDATE trips SET payment_status = 'pagado' WHERE id = ? AND passenger_id = ?");
        $stmtPay->bind_param("ii", $tripId, $userId);
        if ($stmtPay->execute() && $stmtPay->affected_rows > 0) {
            header("Location: recibo_viaje.php?trip_id=$tripId");
            exit;
        } else {
            $error = "Error al procesar el pago. Intenta de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago del Viaje - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <link rel="stylesheet" href="modal.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
        .glass { background: rgba(30, 41, 59, 0.85); backdrop-filter: blur(8px); border-radius: 1.25rem; border: 1px solid rgba(255, 255, 255, 0.08); }
        .pay-icon { font-size: 3rem; }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-center items-center text-gray-300 p-4">

    <div class="glass w-full max-w-md mx-auto p-8 shadow-2xl">
        <div class="text-center mb-6">
            <div class="pay-icon mb-2">💳</div>
            <h1 class="text-2xl font-extrabold text-white">Pago del Viaje</h1>
            <p class="text-gray-400 text-sm mt-1">Confirma el pago para finalizar tu viaje</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/90 text-center p-3 rounded-lg mb-4 text-white shadow-md w-full">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Resumen del viaje -->
        <div class="space-y-3 mb-6">
            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                <span class="text-gray-400 text-sm">Servicio</span>
                <span class="text-white font-semibold"><?php echo htmlspecialchars($serviceLabels[$trip['service_type']] ?? 'Economico'); ?></span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                <span class="text-gray-400 text-sm">Conductor</span>
                <span class="text-white font-semibold"><?php echo htmlspecialchars($trip['driver_name'] ?? 'No asignado'); ?></span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                <span class="text-gray-400 text-sm">Origen</span>
                <span class="text-white text-sm text-right max-w-xs truncate"><?php echo htmlspecialchars($trip['pickup_address']); ?></span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                <span class="text-gray-400 text-sm">Destino</span>
                <span class="text-white text-sm text-right max-w-xs truncate"><?php echo htmlspecialchars($trip['destination_address']); ?></span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                <span class="text-gray-400 text-sm">Distancia</span>
                <span class="text-indigo-400 font-semibold"><?php echo number_format($trip['distance'], 2); ?> km</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                <span class="text-gray-400 text-sm">Metodo de pago</span>
                <span class="text-blue-400 font-semibold"><?php echo htmlspecialchars($paymentLabels[$trip['payment_method']] ?? 'Efectivo'); ?></span>
            </div>
            <div class="flex justify-between items-center py-3 bg-gray-800/50 rounded-lg px-3 mt-2">
                <span class="text-white font-bold text-lg">Total a pagar</span>
                <span class="text-green-400 font-extrabold text-2xl">$<?php echo number_format($trip['fare'], 2); ?></span>
            </div>
        </div>

        <!-- Boton Pagar -->
        <form action="pagar_viaje.php?trip_id=<?php echo $tripId; ?>" method="POST" id="payment-form">
            <?php echo csrfField(); ?>
            <button type="button" onclick="confirmPayment()" class="w-full bg-gradient-to-r from-green-600 to-green-500 text-white py-4 rounded-xl font-bold shadow-xl hover:scale-105 transition text-lg">
                Pagar $<?php echo number_format($trip['fare'], 2); ?>
            </button>
        </form>
    </div>

    <script src="modal.js"></script>
    <script>
        function confirmPayment() {
            const method = '<?php echo htmlspecialchars($paymentLabels[$trip['payment_method']] ?? 'Efectivo'); ?>';
            const amount = '$<?php echo number_format($trip['fare'], 2); ?>';

            RideIt.confirm({
                title: 'Confirmar pago',
                message: '¿Confirmas el pago de <strong>' + amount + '</strong> con <strong>' + method + '</strong>?',
                type: 'info',
                confirmText: 'Confirmar pago',
                confirmClass: 'btn-success',
                onConfirm: () => document.getElementById('payment-form').submit()
            });
        }
    </script>
</body>
</html>
