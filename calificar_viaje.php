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
$mensaje = "";
$error = "";

// Verificar que el viaje existe, pertenece al usuario y esta completado
$stmt = $conn->prepare("SELECT id, destination_address, fare, rating, driver_id FROM trips WHERE id = ? AND passenger_id = ? AND status = 'completado'");
$stmt->bind_param("ii", $tripId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$trip = $result->fetch_assoc();

if (!$trip) {
    header("Location: dashboardpa.php");
    exit;
}

if (!is_null($trip['rating'])) {
    $error = "Este viaje ya fue calificado.";
}

// Obtener nombre del conductor
$driverName = "Desconocido";
if ($trip['driver_id']) {
    $stmt2 = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt2->bind_param("i", $trip['driver_id']);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($row2 = $res2->fetch_assoc()) {
        $driverName = $row2['name'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_null($trip['rating'])) {
    if (!validateCsrfToken()) {
        $error = "Token de seguridad invalido. Recarga la pagina.";
    } else {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $error = "Selecciona una calificacion valida (1-5 estrellas).";
        } else {
            $stmt = $conn->prepare("UPDATE trips SET rating = ? WHERE id = ? AND passenger_id = ?");
            $stmt->bind_param("iii", $rating, $tripId, $userId);
            if ($stmt->execute()) {
                $mensaje = "Gracias por tu calificacion.";
                $trip['rating'] = $rating;
            } else {
                $error = "Error al guardar la calificacion.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificar Viaje - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(135deg, #01579B 0%, #003366 100%); }
        .glass { background: rgba(23, 37, 84, 0.72); backdrop-filter: blur(5px); border-radius: 1.25rem; border: 1px solid rgba(255, 255, 255, 0.10); }
        .star { cursor: pointer; font-size: 2.5rem; color: #4b5563; transition: color 0.2s; }
        .star.active, .star:hover { color: #fbbf24; }
        textarea { background-color: #1e293b; color: #f8fafc; border: 1px solid #475569; border-radius: 8px; }
        textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px #3b82f6; }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-center items-center text-gray-300">

    <div class="glass w-full max-w-md mx-auto p-8 flex flex-col items-center shadow-2xl">
        <h1 class="text-2xl font-extrabold text-white mb-2 text-center">Calificar Viaje</h1>
        <p class="text-blue-200 mb-4 text-center text-sm">Destino: <?php echo htmlspecialchars($trip['destination_address']); ?></p>
        <p class="text-gray-400 mb-2 text-sm">Conductor: <?php echo htmlspecialchars($driverName); ?></p>
        <p class="text-gray-400 mb-6 text-sm">Tarifa: $<?php echo number_format($trip['fare'], 2); ?></p>

        <?php if ($mensaje): ?>
            <div class="bg-green-500/90 text-center p-3 rounded-lg mb-4 text-white shadow-md w-full">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
            <a href="dashboardpa.php" class="w-full bg-gradient-to-r from-blue-600 to-blue-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-center block mt-4">
                Volver al inicio
            </a>
        <?php elseif ($error): ?>
            <div class="bg-red-500/90 text-center p-3 rounded-lg mb-4 text-white shadow-md w-full">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <a href="dashboardpa.php" class="w-full bg-gradient-to-r from-blue-600 to-blue-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-center block mt-4">
                Volver al inicio
            </a>
        <?php else: ?>
            <form action="calificar_viaje.php?trip_id=<?php echo $tripId; ?>" method="POST" class="w-full flex flex-col gap-4">
                <?php echo csrfField(); ?>
                <div class="flex justify-center gap-2" id="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="rating-input" value="0">
                <p id="rating-text" class="text-center text-sm text-gray-400">Selecciona una calificacion</p>

                <div>
                    <label for="comment" class="block text-sm font-medium text-gray-200 mb-1">Comentario (opcional)</label>
                    <textarea name="comment" id="comment" rows="3" placeholder="Escribe un comentario sobre tu experiencia..." class="w-full px-4 py-3"></textarea>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-yellow-500 to-yellow-400 text-yellow-900 py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-lg">
                    Enviar calificacion
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        const labels = ['', 'Muy malo', 'Malo', 'Regular', 'Bueno', 'Excelente'];
        function setRating(value) {
            document.getElementById('rating-input').value = value;
            document.getElementById('rating-text').textContent = labels[value];
            document.querySelectorAll('.star').forEach(s => {
                s.classList.toggle('active', parseInt(s.dataset.value) <= value);
            });
        }
    </script>
</body>
</html>
