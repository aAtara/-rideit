<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login_admin.php");
    exit;
}

$mensaje = "";
$filtro = $_GET['filtro'] ?? 'todos';

// Eliminar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCsrfToken()) {
        $mensaje = "Token invalido.";
    } else {
        $targetId = (int)$_POST['user_id'];

        if ($_POST['action'] === 'eliminar') {
            // No eliminar admins
            $check = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $check->bind_param("i", $targetId);
            $check->execute();
            $r = $check->get_result()->fetch_assoc();
            if ($r && $r['role'] !== 'admin') {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
                $stmt->bind_param("i", $targetId);
                $stmt->execute();
                $mensaje = "Usuario eliminado correctamente.";
            }
        } elseif ($_POST['action'] === 'cambiar_rol') {
            $nuevoRol = $_POST['nuevo_rol'];
            if (in_array($nuevoRol, ['pasajero', 'conductor'])) {
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ? AND role != 'admin'");
                $stmt->bind_param("si", $nuevoRol, $targetId);
                $stmt->execute();
                $mensaje = "Rol actualizado.";
            }
        }
    }
}

// Buscar usuarios
$buscar = trim($_GET['buscar'] ?? '');
$whereClause = "WHERE role != 'admin'";
if ($filtro === 'pasajeros') $whereClause .= " AND role = 'pasajero'";
if ($filtro === 'conductores') $whereClause .= " AND role = 'conductor'";
if (!empty($buscar)) $whereClause .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";

$sql = "SELECT id, name, email, phone, role, plate, created_at FROM users $whereClause ORDER BY created_at DESC";

if (!empty($buscar)) {
    $stmt = $conn->prepare($sql);
    $like = "%$buscar%";
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener promedios de calificacion para conductores
$ratings = [];
$ratingResult = $conn->query("
    SELECT driver_id, ROUND(AVG(rating),1) AS promedio, COUNT(rating) AS total
    FROM trips WHERE rating IS NOT NULL AND driver_id IS NOT NULL
    GROUP BY driver_id
");
if ($ratingResult) {
    while ($row = $ratingResult->fetch_assoc()) {
        $ratings[$row['driver_id']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Usuarios - Admin RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <link rel="stylesheet" href="modal.css">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .card { background: #1e293b; border: 1px solid #334155; }
        input, select { background: #0f172a; color: #e2e8f0; border: 1px solid #475569; }
        input:focus, select:focus { outline: none; border-color: #818cf8; box-shadow: 0 0 0 2px rgba(129,140,248,0.3); }
    </style>
</head>
<body class="font-sans min-h-screen">

    <!-- Header -->
    <header class="bg-gradient-to-r from-indigo-900 to-purple-900 text-white px-4 py-4 flex justify-between items-center shadow-lg">
        <div class="flex items-center gap-3">
            <a href="admin_panel.php" class="text-indigo-300 hover:text-white text-sm">← Dashboard</a>
            <h1 class="text-xl font-bold">👥 Gestion de Usuarios</h1>
        </div>
        <span class="text-sm text-indigo-300"><?php echo count($usuarios); ?> usuarios</span>
    </header>

    <main class="p-4 md:p-8 max-w-6xl mx-auto pb-24">

        <?php if ($mensaje): ?>
            <div class="bg-green-600/80 p-3 rounded-lg mb-4 text-white text-center font-bold"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <!-- Filtros y busqueda -->
        <div class="card p-4 rounded-xl mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-3">
                <input type="text" name="buscar" placeholder="Buscar por nombre, email o telefono..."
                    value="<?php echo htmlspecialchars($buscar); ?>"
                    class="flex-1 px-4 py-2 rounded-lg">
                <select name="filtro" class="px-4 py-2 rounded-lg">
                    <option value="todos" <?php echo $filtro==='todos'?'selected':''; ?>>Todos</option>
                    <option value="pasajeros" <?php echo $filtro==='pasajeros'?'selected':''; ?>>Solo Pasajeros</option>
                    <option value="conductores" <?php echo $filtro==='conductores'?'selected':''; ?>>Solo Conductores</option>
                </select>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700 transition">
                    Buscar
                </button>
            </form>
        </div>

        <!-- Tabla de usuarios -->
        <div class="card rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-800 text-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nombre</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Telefono</th>
                            <th class="px-4 py-3 text-left">Rol</th>
                            <th class="px-4 py-3 text-left">Placa</th>
                            <th class="px-4 py-3 text-left">Calificacion</th>
                            <th class="px-4 py-3 text-left">Registro</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">No se encontraron usuarios.</td></tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $u): ?>
                                <tr class="border-t border-gray-700 hover:bg-gray-800/50">
                                    <td class="px-4 py-3 text-gray-400"><?php echo $u['id']; ?></td>
                                    <td class="px-4 py-3 text-white font-medium"><?php echo htmlspecialchars($u['name']); ?></td>
                                    <td class="px-4 py-3 text-gray-300"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td class="px-4 py-3 text-gray-300"><?php echo htmlspecialchars($u['phone']); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $u['role']==='conductor'?'bg-green-600/30 text-green-400':'bg-blue-600/30 text-blue-400'; ?>">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-400"><?php echo htmlspecialchars($u['plate'] ?? '-'); ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($u['role'] === 'conductor' && isset($ratings[$u['id']])): ?>
                                            <span class="text-yellow-400 font-bold">⭐ <?php echo $ratings[$u['id']]['promedio']; ?></span>
                                            <span class="text-gray-500 text-xs">(<?php echo $ratings[$u['id']]['total']; ?>)</span>
                                        <?php elseif ($u['role'] === 'conductor'): ?>
                                            <span class="text-gray-500 text-xs">Sin calificaciones</span>
                                        <?php else: ?>
                                            <span class="text-gray-600">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-gray-400 text-xs"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center gap-2">
                                            <form id="role-<?php echo $u['id']; ?>" method="POST" class="inline">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="action" value="cambiar_rol">
                                                <input type="hidden" name="nuevo_rol" value="<?php echo $u['role']==='pasajero'?'conductor':'pasajero'; ?>">
                                                <button type="button" onclick="confirmChangeRole(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['name'])); ?>', '<?php echo $u['role']==='pasajero'?'conductor':'pasajero'; ?>')" class="bg-indigo-600 text-white px-3 py-1 rounded text-xs hover:bg-indigo-700" title="Cambiar a <?php echo $u['role']==='pasajero'?'conductor':'pasajero'; ?>">
                                                    🔄
                                                </button>
                                            </form>
                                            <form id="delete-<?php echo $u['id']; ?>" method="POST" class="inline">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="action" value="eliminar">
                                                <button type="button" onclick="confirmDeleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['name'])); ?>')" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Mobile nav -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-700 flex justify-around py-2 z-50">
        <a href="admin_panel.php" class="text-center text-xs text-gray-400 p-2">📊<br>Inicio</a>
        <a href="admin_usuarios.php" class="text-center text-xs text-indigo-400 p-2">👥<br>Usuarios</a>
        <a href="admin_alertas.php" class="text-center text-xs text-gray-400 p-2">🚨<br>SOS</a>
        <a href="admin_tarifas.php" class="text-center text-xs text-gray-400 p-2">💰<br>Tarifas</a>
    </div>

    <script src="modal.js"></script>
    <script>
        function confirmChangeRole(userId, userName, nuevoRol) {
            RideIt.confirm({
                title: 'Cambiar rol de usuario',
                message: '¿Deseas cambiar el rol de <strong>' + userName + '</strong> a <strong>' + nuevoRol + '</strong>?',
                type: 'warning',
                confirmText: 'Cambiar rol',
                onConfirm: () => document.getElementById('role-' + userId).submit()
            });
        }

        function confirmDeleteUser(userId, userName) {
            RideIt.confirm({
                title: 'Eliminar usuario',
                message: '¿Estas seguro de eliminar a <strong>' + userName + '</strong>? Esta accion es <strong>irreversible</strong>.',
                type: 'danger',
                confirmText: 'Eliminar',
                onConfirm: () => document.getElementById('delete-' + userId).submit()
            });
        }
    </script>
</body>
</html>
