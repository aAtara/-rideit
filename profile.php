<?php
include 'db.php';
include 'csrf.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_conductor.php");
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
    <link rel="stylesheet" href="modal.css">
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
                <div class="<?php echo strpos($message, 'Error') !== false || strpos($message, 'Solo') !== false || strpos($message, 'superar') !== false || strpos($message, 'invalido') !== false ? 'bg-red-500' : 'bg-green-500'; ?> text-white p-4 rounded-lg mb-4 flex items-center gap-2">
                    <span><?php echo strpos($message, 'Error') !== false || strpos($message, 'Solo') !== false ? '✕' : '✓'; ?></span>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="profile.php" method="POST" enctype="multipart/form-data">

            <div class="text-center mb-6">
                <img id="profile-pic" src="<?php echo htmlspecialchars($user['photo'] ?? 'https://via.placeholder.com/150'); ?>" alt="Foto de perfil" class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-blue-500 object-cover">
                <label for="photo" class="bg-blue-500 text-white px-4 py-2 rounded-lg cursor-pointer hover:bg-blue-600 transition">
                    Cambiar Foto
                </label>
                <input id="photo" type="file" name="photo" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewPhoto(event)">
            </div>
                <?php echo csrfField(); ?>
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-300">Nombre <span class="text-red-400">*</span></label>
                    <input id="name" name="name" type="text" required minlength="3" maxlength="100" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-900 text-gray-100 focus:outline-none focus:ring focus:ring-blue-500" value="<?php echo htmlspecialchars($user['name']); ?>">
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-300">Descripcion</label>
                    <textarea id="description" name="description" maxlength="500" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-900 text-gray-100 focus:outline-none focus:ring focus:ring-blue-500" rows="4"><?php echo htmlspecialchars($user['description']); ?></textarea>
                    <p class="text-xs text-gray-500 mt-1"><span id="desc-count">0</span>/500 caracteres</p>
                </div>

                <div class="mb-4">
                    <label for="phone" class="block text-sm font-medium text-gray-300">Telefono <span class="text-red-400">*</span></label>
                    <input id="phone" name="phone" type="tel" required pattern="[0-9]{7,15}" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-900 text-gray-100 focus:outline-none focus:ring focus:ring-blue-500" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    <p class="text-xs text-gray-500 mt-1">Solo numeros, 7-15 digitos</p>
                </div>

                <div class="mb-4">
                    <label for="plate" class="block text-sm font-medium text-gray-300">Placa del Vehiculo <span class="text-red-400">*</span></label>
                    <input id="plate" name="plate" type="text" required maxlength="20" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-900 text-gray-100 focus:outline-none focus:ring focus:ring-blue-500" value="<?php echo htmlspecialchars($user['plate']); ?>">
                </div>

                <button type="button" onclick="confirmSaveProfile()" class="bg-green-500 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-600 w-full transition">
                    Guardar Cambios
                </button>
            </form>

            <!-- Seccion eliminar cuenta -->
            <div class="mt-8 pt-6 border-t border-gray-700">
                <h3 class="text-lg font-bold text-red-400 mb-2">Zona de peligro</h3>
                <p class="text-sm text-gray-400 mb-4">Al eliminar tu cuenta se borraran todos tus datos permanentemente. Los viajes completados se anonimizaran en el historial.</p>
                <button type="button" onclick="confirmDeleteAccount()" class="bg-red-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-red-700 w-full transition">
                    Eliminar mi cuenta
                </button>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-center py-6 text-sm text-gray-400 mt-10">
        <p>&copy; 2025 RideIt Conductores. Todos los derechos reservados.</p>
    </footer>

    <script src="modal.js"></script>
    <script>
        function previewPhoto(event) {
            const file = event.target.files[0];
            if (!file) return;
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                RideIt.alert({ title: 'Formato no valido', message: 'Solo se permiten imagenes JPG, PNG, GIF o WebP.', type: 'danger' });
                event.target.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                RideIt.alert({ title: 'Archivo muy grande', message: 'La imagen no debe superar 5MB. Tu archivo pesa ' + (file.size / 1024 / 1024).toFixed(1) + 'MB.', type: 'danger' });
                event.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function() {
                document.getElementById('profile-pic').src = reader.result;
                RideIt.toast('Foto seleccionada. Guarda los cambios para aplicarla.', 'info');
            };
            reader.readAsDataURL(file);
        }

        // Contador de caracteres
        const descEl = document.getElementById('description');
        const descCount = document.getElementById('desc-count');
        if (descEl && descCount) {
            descCount.textContent = descEl.value.length;
            descEl.addEventListener('input', () => { descCount.textContent = descEl.value.length; });
        }

        function confirmSaveProfile() {
            const name = document.getElementById('name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const plate = document.getElementById('plate').value.trim();

            if (!name || name.length < 3) {
                RideIt.alert({ title: 'Nombre requerido', message: 'El nombre debe tener al menos 3 caracteres.', type: 'warning' });
                return;
            }
            if (!phone || !/^[0-9]{7,15}$/.test(phone)) {
                RideIt.alert({ title: 'Telefono invalido', message: 'Ingresa un numero de telefono valido (solo numeros, 7-15 digitos).', type: 'warning' });
                return;
            }
            if (!plate) {
                RideIt.alert({ title: 'Placa requerida', message: 'Debes ingresar la placa de tu vehiculo.', type: 'warning' });
                return;
            }

            RideIt.confirm({
                title: 'Guardar cambios',
                message: '¿Deseas guardar los cambios en tu perfil?',
                type: 'info',
                confirmText: 'Guardar',
                confirmClass: 'btn-success',
                onConfirm: () => document.querySelector('form[action="profile.php"]').submit()
            });
        }

        function confirmDeleteAccount() {
            RideIt.confirm({
                title: 'Eliminar cuenta',
                message: 'Esta accion es <strong>irreversible</strong>. Se eliminaran todos tus datos personales y preferencias. ¿Deseas continuar?',
                type: 'danger',
                confirmText: 'Si, continuar',
                onConfirm: () => {
                    RideIt.prompt({
                        title: 'Confirmar eliminacion',
                        message: 'Ingresa tu contraseña actual para confirmar la eliminacion de tu cuenta.',
                        placeholder: 'Tu contraseña',
                        inputType: 'password',
                        type: 'danger',
                        confirmText: 'Eliminar cuenta',
                        onConfirm: (password) => {
                            if (!password) {
                                RideIt.alert({ title: 'Error', message: 'Debes ingresar tu contraseña.', type: 'danger' });
                                return;
                            }
                            const formData = new FormData();
                            formData.append('password', password);
                            formData.append('csrf_token', '<?php echo htmlspecialchars(generateCsrfToken()); ?>');

                            fetch('delete_account.php', { method: 'POST', body: formData })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        RideIt.alert({
                                            title: 'Cuenta eliminada',
                                            message: data.message,
                                            type: 'success',
                                            onClose: () => window.location.href = data.redirect
                                        });
                                    } else {
                                        RideIt.alert({ title: 'Error', message: data.message, type: 'danger' });
                                    }
                                })
                                .catch(() => {
                                    RideIt.alert({ title: 'Error', message: 'Error de conexion. Intenta de nuevo.', type: 'danger' });
                                });
                        }
                    });
                }
            });
        }
    </script>

</body>
</html>
