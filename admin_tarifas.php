<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login_admin.php");
    exit;
}

require_once 'config.php';
$mensaje = "";
$error = "";

// Leer valores actuales del .env
$envFile = __DIR__ . '/.env';
$envContent = file_exists($envFile) ? file_get_contents($envFile) : '';

$tarifaBase = TARIFA_BASE;
$tarifaPorKm = TARIFA_POR_KM;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = "Token invalido.";
    } else {
        $nuevaBase = (float)$_POST['tarifa_base'];
        $nuevaPorKm = (float)$_POST['tarifa_por_km'];

        if ($nuevaBase < 0 || $nuevaPorKm < 0) {
            $error = "Las tarifas no pueden ser negativas.";
        } elseif ($nuevaBase > 1000 || $nuevaPorKm > 500) {
            $error = "Las tarifas exceden los limites permitidos.";
        } else {
            // Actualizar .env
            if (file_exists($envFile)) {
                $content = file_get_contents($envFile);
                $content = preg_replace('/^TARIFA_BASE=.*/m', 'TARIFA_BASE=' . $nuevaBase, $content);
                $content = preg_replace('/^TARIFA_POR_KM=.*/m', 'TARIFA_POR_KM=' . $nuevaPorKm, $content);
                file_put_contents($envFile, $content);
                $tarifaBase = $nuevaBase;
                $tarifaPorKm = $nuevaPorKm;
                $mensaje = "Tarifas actualizadas correctamente. Base: \$$nuevaBase, Por km: \$$nuevaPorKm";
            } else {
                $error = "No se encontro el archivo de configuracion.";
            }
        }
    }
}

// Calcular ejemplos
$ejemplos = [5, 10, 15, 20, 30, 50];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Tarifas - Admin RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <link rel="stylesheet" href="modal.css">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .card { background: #1e293b; border: 1px solid #334155; }
        input[type="number"] { background: #0f172a; color: #e2e8f0; border: 1px solid #475569; }
        input:focus { outline: none; border-color: #22c55e; box-shadow: 0 0 0 2px rgba(34,197,94,0.3); }
    </style>
</head>
<body class="font-sans min-h-screen">

    <header class="bg-gradient-to-r from-green-900 to-emerald-800 text-white px-4 py-4 flex justify-between items-center shadow-lg">
        <div class="flex items-center gap-3">
            <a href="admin_panel.php" class="text-green-300 hover:text-white text-sm">← Dashboard</a>
            <h1 class="text-xl font-bold">💰 Configurar Tarifas</h1>
        </div>
    </header>

    <main class="p-4 md:p-8 max-w-3xl mx-auto pb-24">

        <?php if ($mensaje): ?>
            <div class="bg-green-600/80 p-3 rounded-lg mb-4 text-white text-center font-bold"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-600/80 p-3 rounded-lg mb-4 text-white text-center font-bold"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Tarifas actuales -->
        <div class="card p-6 rounded-xl mb-6">
            <h2 class="text-lg font-bold text-white mb-4">Tarifas Actuales</h2>
            <div class="grid grid-cols-2 gap-6">
                <div class="text-center">
                    <p class="text-gray-400 text-sm mb-1">Tarifa Base</p>
                    <p class="text-4xl font-bold text-green-400">$<?php echo number_format($tarifaBase, 2); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Se cobra al iniciar el viaje</p>
                </div>
                <div class="text-center">
                    <p class="text-gray-400 text-sm mb-1">Tarifa por Kilometro</p>
                    <p class="text-4xl font-bold text-green-400">$<?php echo number_format($tarifaPorKm, 2); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Se cobra por cada km recorrido</p>
                </div>
            </div>
        </div>

        <!-- Formulario para editar -->
        <div class="card p-6 rounded-xl mb-6">
            <h2 class="text-lg font-bold text-white mb-4">Modificar Tarifas</h2>
            <form method="POST" class="space-y-4">
                <?php echo csrfField(); ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="tarifa_base" class="block text-sm font-medium text-gray-300 mb-1">Tarifa Base ($)</label>
                        <input type="number" name="tarifa_base" id="tarifa_base" step="0.50" min="0" max="1000"
                            value="<?php echo $tarifaBase; ?>"
                            class="w-full px-4 py-3 rounded-lg text-lg font-bold"
                            oninput="actualizarEjemplos()">
                    </div>
                    <div>
                        <label for="tarifa_por_km" class="block text-sm font-medium text-gray-300 mb-1">Tarifa por Kilometro ($)</label>
                        <input type="number" name="tarifa_por_km" id="tarifa_por_km" step="0.50" min="0" max="500"
                            value="<?php echo $tarifaPorKm; ?>"
                            class="w-full px-4 py-3 rounded-lg text-lg font-bold"
                            oninput="actualizarEjemplos()">
                    </div>
                </div>

                <p class="text-xs text-gray-500">Formula: Tarifa Total = Base + (Kilometros x Tarifa por Km)</p>

                <button type="button" onclick="confirmSaveTarifas()" class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white py-3 rounded-lg font-bold hover:from-green-700 hover:to-emerald-700 transition text-lg">
                    Guardar Nuevas Tarifas
                </button>
            </form>
        </div>

        <!-- Simulador de precios -->
        <div class="card p-6 rounded-xl">
            <h2 class="text-lg font-bold text-white mb-4">Simulador de Precios</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-800 text-gray-300">
                        <tr>
                            <th class="px-4 py-2 text-left">Distancia</th>
                            <th class="px-4 py-2 text-left">Base</th>
                            <th class="px-4 py-2 text-left">Recorrido</th>
                            <th class="px-4 py-2 text-left">Total</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-ejemplos">
                        <?php foreach ($ejemplos as $km): ?>
                            <tr class="border-t border-gray-700">
                                <td class="px-4 py-2"><?php echo $km; ?> km</td>
                                <td class="px-4 py-2 text-gray-400">$<?php echo number_format($tarifaBase, 2); ?></td>
                                <td class="px-4 py-2 text-gray-400">$<?php echo number_format($km * $tarifaPorKm, 2); ?></td>
                                <td class="px-4 py-2 text-green-400 font-bold">$<?php echo number_format($tarifaBase + $km * $tarifaPorKm, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
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
        <a href="admin_tarifas.php" class="text-center text-xs text-green-400 p-2">💰<br>Tarifas</a>
    </div>

    <script src="modal.js"></script>
    <script>
        const kms = [5, 10, 15, 20, 30, 50];
        function actualizarEjemplos() {
            const base = parseFloat(document.getElementById('tarifa_base').value) || 0;
            const pkm = parseFloat(document.getElementById('tarifa_por_km').value) || 0;
            let html = '';
            kms.forEach(km => {
                const recorrido = km * pkm;
                const total = base + recorrido;
                html += '<tr class="border-t border-gray-700">';
                html += '<td class="px-4 py-2">' + km + ' km</td>';
                html += '<td class="px-4 py-2 text-gray-400">$' + base.toFixed(2) + '</td>';
                html += '<td class="px-4 py-2 text-gray-400">$' + recorrido.toFixed(2) + '</td>';
                html += '<td class="px-4 py-2 text-green-400 font-bold">$' + total.toFixed(2) + '</td>';
                html += '</tr>';
            });
            document.getElementById('tabla-ejemplos').innerHTML = html;
        }

        function confirmSaveTarifas() {
            const base = parseFloat(document.getElementById('tarifa_base').value) || 0;
            const pkm = parseFloat(document.getElementById('tarifa_por_km').value) || 0;

            if (base < 0 || pkm < 0) {
                RideIt.alert({ title: 'Valor invalido', message: 'Las tarifas no pueden ser negativas.', type: 'danger' });
                return;
            }
            if (base > 1000 || pkm > 500) {
                RideIt.alert({ title: 'Valor excedido', message: 'Las tarifas exceden los limites permitidos (Base max $1000, Por km max $500).', type: 'danger' });
                return;
            }

            const ejemplo10 = (base + pkm * 10).toFixed(2);
            RideIt.confirm({
                title: 'Actualizar tarifas',
                message: '<strong>Tarifa base:</strong> $' + base.toFixed(2) + '<br><strong>Por kilometro:</strong> $' + pkm.toFixed(2) + '<br><strong>Ejemplo 10 km:</strong> $' + ejemplo10 + '<br><br>¿Confirmas estos nuevos valores?',
                type: 'warning',
                confirmText: 'Guardar tarifas',
                confirmClass: 'btn-success',
                onConfirm: () => document.querySelector('form').submit()
            });
        }
    </script>

</body>
</html>
