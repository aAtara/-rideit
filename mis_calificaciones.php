<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_conductor.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener promedio y total
$stmt = $conn->prepare("
    SELECT ROUND(AVG(rating), 1) AS promedio, COUNT(rating) AS total,
           SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS r5,
           SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS r4,
           SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS r3,
           SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS r2,
           SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS r1
    FROM trips WHERE driver_id = ? AND rating IS NOT NULL
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$promedio = $stats['promedio'] ?? 0;
$total = $stats['total'] ?? 0;
$r5 = $stats['r5'] ?? 0;
$r4 = $stats['r4'] ?? 0;
$r3 = $stats['r3'] ?? 0;
$r2 = $stats['r2'] ?? 0;
$r1 = $stats['r1'] ?? 0;

// Obtener calificaciones individuales
$stmt2 = $conn->prepare("
    SELECT t.rating, t.comment, t.destination_address, t.fare, t.completed_at, u.name AS pasajero
    FROM trips t
    JOIN users u ON t.passenger_id = u.id
    WHERE t.driver_id = ? AND t.rating IS NOT NULL
    ORDER BY t.completed_at DESC
    LIMIT 50
");
$stmt2->bind_param("i", $userId);
$stmt2->execute();
$calificaciones = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$starsLabels = ['', 'Muy malo', 'Malo', 'Regular', 'Bueno', 'Excelente'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Calificaciones - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <link rel="stylesheet" href="modal.css">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .card { background: #1e293b; border: 1px solid #334155; }
        .star-filled { color: #fbbf24; }
        .star-empty { color: #374151; }
        .bar-bg { background: #374151; }
    </style>
</head>
<body class="font-sans min-h-screen">

    <header class="bg-gradient-to-r from-gray-800 via-gray-900 to-black text-white p-4 flex justify-between items-center shadow-md">
        <div class="flex items-center gap-3">
            <a href="dashboard.php" class="text-gray-400 hover:text-white text-sm">← Volver</a>
            <h1 class="text-xl font-bold">⭐ Mis Calificaciones</h1>
        </div>
    </header>

    <main class="p-4 md:p-8 max-w-3xl mx-auto">

        <!-- Resumen -->
        <div class="card p-6 rounded-xl mb-6 text-center">
            <p class="text-6xl font-bold text-yellow-400 mb-2"><?php echo $promedio ?: '0.0'; ?></p>
            <div class="flex justify-center gap-1 mb-2">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="text-2xl <?php echo $i <= round($promedio) ? 'star-filled' : 'star-empty'; ?>">★</span>
                <?php endfor; ?>
            </div>
            <p class="text-gray-400 text-sm"><?php echo $total; ?> calificaciones en total</p>

            <?php if ($promedio >= 4.5): ?>
                <div class="mt-3 inline-block bg-green-600/30 text-green-400 px-4 py-1 rounded-full text-sm font-bold">Conductor Destacado</div>
            <?php elseif ($promedio >= 3.5): ?>
                <div class="mt-3 inline-block bg-blue-600/30 text-blue-400 px-4 py-1 rounded-full text-sm font-bold">Buen Conductor</div>
            <?php elseif ($total > 0): ?>
                <div class="mt-3 inline-block bg-yellow-600/30 text-yellow-400 px-4 py-1 rounded-full text-sm font-bold">Puede mejorar</div>
            <?php endif; ?>
        </div>

        <!-- Distribucion de estrellas -->
        <div class="card p-6 rounded-xl mb-6">
            <h2 class="text-lg font-bold text-white mb-4">Distribucion</h2>
            <?php
            $barras = [5 => $r5, 4 => $r4, 3 => $r3, 2 => $r2, 1 => $r1];
            foreach ($barras as $star => $count):
                $pct = $total > 0 ? round(($count / $total) * 100) : 0;
            ?>
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-sm w-6 text-right text-yellow-400"><?php echo $star; ?>★</span>
                    <div class="flex-1 bar-bg rounded-full h-3">
                        <div class="h-3 rounded-full bg-yellow-400 transition-all" style="width: <?php echo $pct; ?>%"></div>
                    </div>
                    <span class="text-xs text-gray-400 w-12"><?php echo $count; ?> (<?php echo $pct; ?>%)</span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Lista de calificaciones -->
        <div class="card p-6 rounded-xl">
            <h2 class="text-lg font-bold text-white mb-4">Calificaciones Recientes</h2>
            <?php if (empty($calificaciones)): ?>
                <p class="text-gray-500 text-center py-4">Aun no tienes calificaciones.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($calificaciones as $cal): ?>
                        <div class="flex justify-between items-center border-b border-gray-700 pb-3">
                            <div>
                                <p class="text-white text-sm font-medium"><?php echo htmlspecialchars($cal['pasajero']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($cal['destination_address']); ?></p>
                                <?php if (!empty($cal['comment'])): ?>
                                    <p class="text-xs text-blue-300 mt-1 italic">"<?php echo htmlspecialchars($cal['comment']); ?>"</p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-500"><?php echo $cal['completed_at'] ? date('d/m/Y', strtotime($cal['completed_at'])) : '-'; ?></p>
                            </div>
                            <div class="text-right">
                                <div class="flex gap-0.5">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="text-lg <?php echo $i <= $cal['rating'] ? 'star-filled' : 'star-empty'; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-xs text-gray-500"><?php echo $starsLabels[$cal['rating']]; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
