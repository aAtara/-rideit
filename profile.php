<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION['user_id'];
$message = "";

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $message = "Token de seguridad invalido.";
    } else {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $phone = trim($_POST['phone']);
        $plate = trim($_POST['plate']);
        $photo = "";

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileType = mime_content_type($_FILES['photo']['tmp_name']);
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            if (!in_array($fileType, $allowedTypes) || !in_array($fileExt, $allowedExtensions)) {
                $message = "Solo se permiten imagenes JPG, PNG, GIF o WebP.";
            } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
                $message = "La imagen no debe superar 5MB.";
            } else {
                $photoDir = 'uploads/';
                if (!is_dir($photoDir)) {
                    mkdir($photoDir, 0755, true);
                }
                $photoName = uniqid() . "." . $fileExt;
                $photoPath = $photoDir . $photoName;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                    $photo = $photoPath;
                } else {
                    $message = "Error al cargar la foto de perfil.";
                }
            }
        }

        if (empty($message)) {
            if ($photo) {
                $stmt = $conn->prepare("UPDATE users SET name = ?, description = ?, phone = ?, plate = ?, photo = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $description, $phone, $plate, $photo, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, description = ?, phone = ?, plate = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $description, $phone, $plate, $userId);
            }

            if ($stmt->execute()) {
                $message = "Perfil actualizado correctamente.";
            } else {
                $message = "Error al actualizar el perfil.";
            }
        }
    }
}

$stmt = $conn->prepare("SELECT name, description, phone, plate, photo FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Conductor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
</head>
<body class="bg-black text-white font-sans">

    <header class="bg-gradient-to-r from-gray-800 via-gray-900 to-black text-white p-4 flex justify-between items-center shadow-md">
        <h1 class="text-xl font-bold">Perfil del Conductor</h1>
        <a href="dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-600 transition">
            Volver
        </a>
    </header>

    <main class="p-4">
        <section class="bg-gray-800 p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4 text-blue-500">Editar Perfil</h2>

            <?php if (!empty($message)): ?>
                <div class="bg-green-500 text-gray-900 p-4 rounded-lg mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="text-center mb-6">
                <img id="profile-pic" src="<?php echo htmlspecialchars($user['photo'] ?? 'https://via.placeholder.com/150'); ?>" alt="Foto de perfil" class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-blue-500">
                <label for="photo" class="bg-blue-500 text-white px-4 py-2 rounded-lg cursor-pointer hover:bg-blue-600">
                    Cambiar Foto
                </label>
                <input id="photo" type="file" name="photo" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewPhoto(event)">
            </div>

            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-300">Nombre</label>
                    <input id="name" name="name" type="text" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-900 text-gray-100 focus:outline-none focus:ring focus:ring-blue-500" value="<?php echo htmlspecialchars($user['name']); ?>">
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-300">Descripcion</label>
                    <textarea id="description" name="description" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-900 text-gray-100 focus:outline-none focus:ring focus:ring-blue-500" rows="4"><?php echo htmlspecialchars($user['description']); ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="phone" class="block text-sm font-medium text-gray-300">Telefono</label>
                    <input id="phone" name="phone" type="tel" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-900 text-gray-100 focus:outline-none focus:ring focus:ring-blue-500" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>

                <div class="mb-4">
                    <label for="plate" class="block text-sm font-medium text-gray-300">Placa del Vehiculo</label>
                    <input id="plate" name="plate" type="text" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-900 text-gray-100 focus:outline-none focus:ring focus:ring-blue-500" value="<?php echo htmlspecialchars($user['plate']); ?>">
                </div>

                <button type="submit" class="bg-green-500 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-600 w-full transition">
                    Guardar Cambios
                </button>
            </form>
        </section>
    </main>

    <footer class="bg-gray-900 text-center py-6 text-sm text-gray-400 mt-10">
        <p>&copy; 2025 RideIt Conductores. Todos los derechos reservados.</p>
    </footer>

    <script>
        function previewPhoto(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const img = document.getElementById('profile-pic');
                img.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>

</body>
</html>
