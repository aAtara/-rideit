<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Usuario';
$mensaje = "";
$error = "";

// Obtener viajes del usuario para asociar al reporte
$stmt = $conn->prepare("SELECT id, destination_address, DATE_FORMAT(created_at, '%d/%m/%Y') AS fecha FROM trips WHERE passenger_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->bind_param("i", $userId);
$stmt->execute();
$tripsResult = $stmt->get_result();
$userTrips = $tripsResult->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = "Token de seguridad invalido. Recarga la pagina.";
    } else {
        $categoria = trim($_POST['categoria'] ?? '');
        $tripIdReport = isset($_POST['trip_id']) ? (int)$_POST['trip_id'] : 0;
        $descripcion = trim($_POST['descripcion'] ?? '');

        $categoriasValidas = ['pago', 'conductor', 'aplicacion', 'seguridad', 'otro'];

        if (empty($categoria) || !in_array($categoria, $categoriasValidas)) {
            $error = "Selecciona una categoria valida.";
        } elseif (empty($descripcion)) {
            $error = "Describe tu problema para poder ayudarte.";
        } else {
            // Generar folio de ticket
            $folio = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $mensaje = "Tu reporte ha sido enviado. Folio: $folio. Nuestro equipo de soporte dara seguimiento.";
        }
    }
}

// FAQ data
$faqs = [
    'General' => [
        '?Como solicito un viaje?' => 'Desde el panel principal, haz clic en "Solicitar un Viaje". Ingresa tu destino, revisa la tarifa estimada y confirma.',
        '?Cuanto tarda en llegar el conductor?' => 'El tiempo depende de la disponibilidad de conductores en tu zona. Normalmente entre 3 y 10 minutos.',
        '?Puedo cancelar un viaje?' => 'Si, puedes cancelar un viaje antes de que el conductor llegue al punto de recogida sin costo adicional.',
    ],
    'Pagos' => [
        '?Como se calcula la tarifa?' => 'La tarifa se calcula con base en la distancia del recorrido. Incluye una tarifa base mas un costo por kilometro.',
        '?Que metodos de pago aceptan?' => 'Actualmente aceptamos pagos en efectivo. Estamos trabajando en integrar pagos con tarjeta.',
        '?La tarifa puede cambiar durante el viaje?' => 'La tarifa estimada puede variar si cambias el destino durante el recorrido.',
    ],
    'Seguridad' => [
        '?Que hago en caso de emergencia?' => 'Durante un viaje activo, usa el boton SOS (Panico) para enviar una alerta inmediata con tu ubicacion.',
        '?Los conductores estan verificados?' => 'Si, todos los conductores pasan por un proceso de verificacion antes de ser aceptados en la plataforma.',
        '?Mis datos personales estan seguros?' => 'Si, tu informacion esta protegida. Consulta nuestro Aviso de Privacidad para mas detalles.',
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda y Soporte - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(135deg, #01579B 0%, #003366 100%); }
        .glass { background: rgba(23, 37, 84, 0.72); backdrop-filter: blur(5px); border-radius: 1.25rem; border: 1px solid rgba(255, 255, 255, 0.10); }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .faq-answer.open { max-height: 200px; }
        input, select, textarea { background-color: #1e293b; color: #f8fafc; border: 1px solid #475569; border-radius: 8px; padding: 0.5rem; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px #3b82f6; }
    </style>
</head>
<body class="min-h-screen text-gray-300">

    <header class="bg-gradient-to-r from-blue-800 to-indigo-900 text-white px-4 py-3 flex justify-between items-center shadow-lg">
        <h1 class="text-xl font-bold">Ayuda y Soporte</h1>
        <a href="dashboardpa.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg text-sm font-bold shadow-md hover:shadow-lg">
            Volver
        </a>
    </header>

    <main class="p-4 max-w-2xl mx-auto space-y-6">

        <!-- Buscador FAQ -->
        <div class="glass p-4">
            <input type="text" id="faq-search" placeholder="Buscar en preguntas frecuentes..." class="w-full px-4 py-3 rounded-xl">
        </div>

        <!-- FAQ -->
        <div class="glass p-6 space-y-6">
            <h2 class="text-xl font-bold text-white">Preguntas Frecuentes</h2>
            <?php foreach ($faqs as $category => $questions): ?>
                <div class="faq-category">
                    <h3 class="text-md font-bold text-blue-300 mb-2"><?php echo htmlspecialchars($category); ?></h3>
                    <?php foreach ($questions as $question => $answer): ?>
                        <div class="faq-item mb-2">
                            <button onclick="toggleFaq(this)" class="faq-question w-full text-left text-sm text-gray-200 bg-gray-800/50 px-4 py-3 rounded-lg hover:bg-gray-700/50 transition flex justify-between items-center">
                                <span><?php echo htmlspecialchars($question); ?></span>
                                <span class="faq-icon">+</span>
                            </button>
                            <div class="faq-answer px-4 py-0 text-sm text-gray-400">
                                <p class="py-2"><?php echo htmlspecialchars($answer); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Contactar Soporte -->
        <div class="glass p-6 space-y-4">
            <h2 class="text-xl font-bold text-white">Contactar Soporte</h2>

            <?php if ($mensaje): ?>
                <div class="bg-green-500/90 text-center p-3 rounded-lg text-white shadow-md">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-500/90 text-center p-3 rounded-lg text-white shadow-md">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="help.php" method="POST" class="space-y-4">
                <?php echo csrfField(); ?>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Categoria del problema</label>
                    <select name="categoria" required class="w-full px-4 py-3 rounded-xl">
                        <option value="">Selecciona una categoria</option>
                        <option value="pago">Pago</option>
                        <option value="conductor">Conductor</option>
                        <option value="aplicacion">Aplicacion</option>
                        <option value="seguridad">Seguridad</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Viaje relacionado (opcional)</label>
                    <select name="trip_id" class="w-full px-4 py-3 rounded-xl">
                        <option value="0">Ninguno</option>
                        <?php foreach ($userTrips as $t): ?>
                            <option value="<?php echo $t['id']; ?>">
                                Viaje a <?php echo htmlspecialchars($t['destination_address']); ?> (<?php echo $t['fecha']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-200 mb-1">Describe tu problema</label>
                    <textarea name="descripcion" rows="4" placeholder="Describe que paso..." required class="w-full px-4 py-3 rounded-xl"></textarea>
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition">
                    Enviar reporte
                </button>
            </form>
        </div>

        <!-- Enlace Aviso de Privacidad -->
        <div class="glass p-4 text-center">
            <a href="privacidad.php" class="text-blue-300 hover:text-blue-400 underline text-sm">Aviso de Privacidad</a>
            <span class="text-gray-500 mx-2">|</span>
            <a href="terminos.php" class="text-blue-300 hover:text-blue-400 underline text-sm">Terminos y Condiciones</a>
        </div>
    </main>

    <script>
        function toggleFaq(btn) {
            const answer = btn.nextElementSibling;
            const icon = btn.querySelector('.faq-icon');
            answer.classList.toggle('open');
            icon.textContent = answer.classList.contains('open') ? '-' : '+';
        }

        document.getElementById('faq-search').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.faq-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(query) ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>
