<?php
// Conexión a la base de datos
$servername = "sql203.infinityfree.com";
$username = "if0_38081973";
$password = "WWr97REqXr216S";
$dbname = "if0_38081973_rideit";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Buscar aplicaciones
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM applications";
if (!empty($search)) {
    $sql .= " WHERE full_name LIKE '%" . $conn->real_escape_string($search) . "%'";
}
$result = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo "<div class='bg-green-500 text-white text-center p-2'>Estado actualizado correctamente</div>";
    } else {
        echo "<div class='bg-red-500 text-white text-center p-2'>Error al actualizar el estado</div>";
    }

    $stmt->close();
}

if (isset($_GET['view_id'])) {
    $view_id = $_GET['view_id'];
    $view_query = $conn->prepare("SELECT * FROM applications WHERE id = ?");
    $view_query->bind_param("i", $view_id);
    $view_query->execute();
    $application_details = $view_query->get_result()->fetch_assoc();
    $view_query->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Aplicaciones - Conductor Uber</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <script>
        // Recargar la página automáticamente cada 5 segundos
        setInterval(function() {
            location.reload();
        }, 5000);
    </script>
</head>
<body class="bg-black text-white font-sans">

    <div class="flex flex-col min-h-screen justify-center items-center px-4">
        <h1 class="text-4xl font-extrabold text-white mb-6 text-center">
            🚖 Gestión de Aplicaciones - <span class="text-blue-400">Conductor Uber</span>
        </h1>

        <form method="GET" class="mb-6 w-full max-w-lg">
            <input 
                type="text" 
                name="search" 
                value="<?php echo htmlspecialchars($search); ?>" 
                placeholder="Buscar por nombre..." 
                class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white">
        </form>

        <div class="w-full max-w-4xl">
            <table class="w-full table-auto text-left">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-4 py-2">Nombre</th>
                        <th class="px-4 py-2">Teléfono</th>
                        <th class="px-4 py-2">Correo</th>
                        <th class="px-4 py-2">Estado</th>
                        <th class="px-4 py-2">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="border-b border-gray-700">
                                <td class="px-4 py-2 text-gray-300"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td class="px-4 py-2 text-gray-300"><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td class="px-4 py-2 text-gray-300"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="px-4 py-2 text-gray-300"><?php echo htmlspecialchars($row['status'] ?? 'Pendiente'); ?></td>
                                <td class="px-4 py-2">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button 
                                            type="submit" 
                                            name="status" 
                                            value="Aceptado" 
                                            class="bg-green-500 text-white px-3 py-1 rounded-lg hover:bg-green-600">Aceptar</button>
                                        <button 
                                            type="submit" 
                                            name="status" 
                                            value="Rechazado" 
                                            class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600">Rechazar</button>
                                    </form>
                                    <a 
                                        href="?view_id=<?php echo $row['id']; ?>" 
                                        class="bg-blue-500 text-white px-3 py-1 rounded-lg hover:bg-blue-600">Ver Detalles</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-center text-gray-400">No se encontraron aplicaciones</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($application_details)): ?>
            <div class="mt-6 w-full max-w-lg bg-gray-800 p-6 rounded-lg shadow-lg">
                <h2 class="text-2xl font-bold text-white mb-4">Detalles de la Aplicación</h2>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($application_details['full_name']); ?></p>
                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($application_details['phone']); ?></p>
                <p><strong>Correo:</strong> <?php echo htmlspecialchars($application_details['email']); ?></p>
                <p><strong>Auto Propio:</strong> <?php echo htmlspecialchars($application_details['own_car']); ?></p>
                <p><strong>Marca y Modelo:</strong> <?php echo htmlspecialchars($application_details['car_details']); ?></p>
                <p><strong>Año del Auto:</strong> <?php echo htmlspecialchars($application_details['car_year']); ?></p>
                <p><strong>Licencia:</strong> <?php echo htmlspecialchars($application_details['license']); ?></p>
                <p><strong>¿Por qué sería buen conductor?:</strong> <?php echo htmlspecialchars($application_details['trustworthy']); ?></p>
                <p><strong>Responsabilidad:</strong> <?php echo htmlspecialchars($application_details['reliability']); ?></p>
                <a href="?" class="mt-4 inline-block bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Cerrar</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-black text-gray-300 text-center py-6 mt-8">
        <p class="text-sm">© 2025 RideIt. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
