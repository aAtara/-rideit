<?php
include 'db.php';
include 'csrf.php';
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, description, photo, phone, payment_method FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userName = $user['name'];
    $userDescription = $user['description'] ?? "Pasajero desde " . date("Y");
    $userPhoto = $user['photo'] ?? "https://via.placeholder.com/150";
    $userPhone = $user['phone'] ?? "No registrado";
    $userPaymentMethod = $user['payment_method'] ?? 'efectivo';
} else {
    session_destroy();
    header("Location: login_pasajero.php");
    exit;
}

$photoNotification = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if (!validateCsrfToken()) {
        $photoNotification = "Token de seguridad invalido.";
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileType = mime_content_type($_FILES['photo']['tmp_name']);
        $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileType, $allowedTypes) || !in_array($fileExt, $allowedExtensions)) {
            $photoNotification = "Solo se permiten imagenes JPG, PNG, GIF o WebP.";
        } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            $photoNotification = "La imagen no debe superar 5MB.";
        } else {
            $photoDir = 'uploads/';
            if (!is_dir($photoDir)) {
                mkdir($photoDir, 0755, true);
            }
            $photoName = 'pa_' . $userId . '_' . uniqid() . '.' . $fileExt;
            $photoPath = $photoDir . $photoName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                $stmt = $conn->prepare("UPDATE users SET photo = ? WHERE id = ?");
                $stmt->bind_param("si", $photoPath, $userId);
                if ($stmt->execute()) {
                    $photoNotification = "Foto de perfil actualizada correctamente.";
                    $userPhoto = $photoPath;
                } else {
                    $photoNotification = "Error al guardar la foto en la base de datos.";
                }
            } else {
                $photoNotification = "Error al subir la foto.";
            }
        }
    }
}

$phoneNotification = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone'])) {
    if (!validateCsrfToken()) {
        $phoneNotification = "Token de seguridad invalido.";
    } else {
        $newPhone = trim($_POST['phone']);

        if (!empty($newPhone) && preg_match('/^[0-9]{7,15}$/', $newPhone)) {
            $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
            $stmt->bind_param("si", $newPhone, $userId);

            if ($stmt->execute()) {
                $phoneNotification = "Numero de telefono actualizado correctamente.";
                $userPhone = $newPhone;
            } else {
                $phoneNotification = "Error al actualizar el numero de telefono.";
            }
        } else {
            $phoneNotification = "Por favor, ingresa un numero de telefono valido (solo digitos, 7-15 caracteres).";
        }
    }
}

$paymentNotification = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    if (!validateCsrfToken()) {
        $paymentNotification = "Token de seguridad invalido.";
    } else {
        $newPayment = trim($_POST['payment_method']);
        $validMethods = ['efectivo', 'tarjeta_debito', 'tarjeta_credito'];
        if (in_array($newPayment, $validMethods)) {
            $stmt = $conn->prepare("UPDATE users SET payment_method = ? WHERE id = ?");
            $stmt->bind_param("si", $newPayment, $userId);
            if ($stmt->execute()) {
                $paymentNotification = "Metodo de pago actualizado correctamente.";
                $userPaymentMethod = $newPayment;
            } else {
                $paymentNotification = "Error al actualizar el metodo de pago.";
            }
        } else {
            $paymentNotification = "Metodo de pago no valido.";
        }
    }
}

$notification = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'], $_POST['address'])) {
    if (!validateCsrfToken()) {
        $notification = "Token de seguridad invalido.";
    } else {
        $type = trim($_POST['type']);
        $address = trim($_POST['address']);

        if (!empty($type) && !empty($address)) {
            $apiKey = GOOGLE_MAPS_API_KEY;
            $addressEncoded = urlencode($address);
            $geoUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=$addressEncoded&key=$apiKey";

            $response = file_get_contents($geoUrl);
            $geoData = json_decode($response, true);

            if ($geoData['status'] === 'OK') {
                $lat = $geoData['results'][0]['geometry']['location']['lat'];
                $lng = $geoData['results'][0]['geometry']['location']['lng'];

                $stmt = $conn->prepare("INSERT INTO preferences (user_id, type, address, lat, lng) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issdd", $userId, $type, $address, $lat, $lng);

                if ($stmt->execute()) {
                    $notification = "Direccion agregada correctamente!";
                } else {
                    $notification = "Error al agregar la direccion.";
                }
            } else {
                $notification = "No se pudieron calcular las coordenadas de la direccion.";
            }
        } else {
            $notification = "Por favor, completa todos los campos.";
        }
    }
}

if (isset($_GET['delete']) && isset($_GET['token'])) {
    if (!hash_equals(($_SESSION['csrf_token'] ?? ''), $_GET['token'])) {
        header("Location: profilepa.php");
        exit;
    }
    $typeToDelete = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM preferences WHERE user_id = ? AND type = ?");
    $stmt->bind_param("is", $userId, $typeToDelete);
    $stmt->execute();
    header("Location: profilepa.php");
    exit;
}

$stmt = $conn->prepare("SELECT type, address, lat, lng FROM preferences WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$preferencesResult = $stmt->get_result();
$preferences = $preferencesResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Pasajero - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <link rel="stylesheet" href="modal.css">
    <style>
        body {
            background: linear-gradient(to right, #1a202c, #2d3748);
            font-family: 'Inter', sans-serif;
        }
        .card { background: rgba(255, 255, 255, 0.1); color: white; }
        .card:hover { transform: scale(1.05); transition: all 0.3s ease-in-out; }
        .btn-add { background: rgba(72, 187, 120, 0.9); }
        .btn-add:hover { background: rgba(56, 142, 94, 0.9); }
        .btn-delete { background: rgba(239, 68, 68, 0.8); }
        .btn-delete:hover { background: rgba(220, 38, 38, 0.9); }
        .notification { background: rgba(72, 187, 120, 0.8); }
        .profile-pic:hover { transform: scale(1.1); transition: 0.3s ease; }
    </style>
</head>
<body class="text-gray-300">

    <div class="flex flex-col min-h-screen">
        <header class="bg-gradient-to-r from-gray-800 to-gray-700 text-white px-4 py-3 flex justify-between items-center shadow-lg">
            <h1 class="text-xl font-bold">🚖 RideIt</h1>
            <a href="dashboardpa.php" class="bg-indigo-500 px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-600 transition">
                Volver
            </a>
        </header>

        <main class="flex-1 p-6">
            <section class="card p-6 rounded-xl shadow-md text-center">
                <div class="relative inline-block">
                    <img id="profile-pic" src="<?php echo htmlspecialchars($userPhoto); ?>" alt="Foto de perfil" class="profile-pic w-32 h-32 rounded-full mx-auto mb-2 border-4 border-indigo-500 object-cover">
                    <form action="" method="POST" enctype="multipart/form-data" id="photo-form" class="mt-2">
                        <?php echo csrfField(); ?>
                        <label for="photo-input" class="bg-indigo-500 text-white text-xs px-3 py-1 rounded-full cursor-pointer hover:bg-indigo-600 transition inline-block">
                            Cambiar foto
                        </label>
                        <input id="photo-input" type="file" name="photo" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewAndSubmitPhoto(event)">
                    </form>
                </div>
                <?php if ($photoNotification): ?>
                    <div class="<?php echo strpos($photoNotification, 'Error') !== false || strpos($photoNotification, 'Solo') !== false || strpos($photoNotification, 'superar') !== false ? 'bg-red-500/80' : 'bg-green-500/80'; ?> text-center p-2 rounded-lg mt-2 text-white text-sm">
                        <?php echo htmlspecialchars($photoNotification); ?>
                    </div>
                <?php endif; ?>
                <h2 id="profile-name" class="text-2xl font-bold text-white mt-3"><?php echo htmlspecialchars($userName); ?></h2>
                <p id="profile-description" class="text-sm text-gray-400 mt-2"><?php echo htmlspecialchars($userDescription); ?></p>
                <p id="profile-phone" class="text-sm text-gray-400 mt-2">Telefono: <?php echo htmlspecialchars($userPhone); ?></p>

                <form action="" method="POST" class="mt-4">
                    <?php echo csrfField(); ?>
                    <input type="text" name="phone" placeholder="Nuevo numero de telefono" class="w-full px-4 py-2 border rounded-lg mb-2" required pattern="[0-9]{7,15}">
                    <button type="submit" class="bg-indigo-500 w-full text-white py-2 rounded-lg font-bold hover:bg-indigo-600 transition">
                        Actualizar Telefono
                    </button>
                </form>
            </section>

            <?php if ($phoneNotification): ?>
                <div class="notification text-center p-4 rounded-lg mt-4 text-white shadow-md">
                    <?php echo htmlspecialchars($phoneNotification); ?>
                </div>
            <?php endif; ?>

            <!-- Metodo de pago -->
            <section class="mt-8">
                <h2 class="text-2xl font-bold mb-4 text-white">Metodo de Pago</h2>
                <div class="card p-4 rounded-xl shadow-md">
                    <p class="text-sm text-gray-400 mb-3">Metodo actual: <span class="text-indigo-400 font-bold"><?php
                        $methodLabels = ['efectivo' => 'Efectivo', 'tarjeta_debito' => 'Tarjeta de Debito', 'tarjeta_credito' => 'Tarjeta de Credito'];
                        echo htmlspecialchars($methodLabels[$userPaymentMethod] ?? 'Efectivo');
                    ?></span></p>
                    <form action="" method="POST">
                        <?php echo csrfField(); ?>
                        <select name="payment_method" class="w-full px-4 py-2 border rounded-lg mb-2 bg-gray-900 text-white border-gray-600">
                            <option value="efectivo" <?php echo $userPaymentMethod === 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                            <option value="tarjeta_debito" <?php echo $userPaymentMethod === 'tarjeta_debito' ? 'selected' : ''; ?>>Tarjeta de Debito</option>
                            <option value="tarjeta_credito" <?php echo $userPaymentMethod === 'tarjeta_credito' ? 'selected' : ''; ?>>Tarjeta de Credito</option>
                        </select>
                        <button type="submit" class="bg-indigo-500 w-full text-white py-2 rounded-lg font-bold hover:bg-indigo-600 transition">
                            Actualizar Metodo de Pago
                        </button>
                    </form>
                </div>
                <?php if ($paymentNotification): ?>
                    <div class="notification text-center p-4 rounded-lg mt-4 text-white shadow-md">
                        <?php echo htmlspecialchars($paymentNotification); ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="mt-8">
                <h2 class="text-2xl font-bold mb-4 text-white">Direcciones Guardadas</h2>
                <div id="preferences" class="space-y-4">
                    <?php foreach ($preferences as $preference): ?>
                        <div class="card p-4 rounded-xl shadow-md flex justify-between items-center">
                            <div>
                                <p class="text-sm font-bold text-indigo-400"><?php echo htmlspecialchars($preference['type']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($preference['address']); ?></p>
                                <p class="text-xs text-gray-500">Lat: <?php echo $preference['lat']; ?>, Lng: <?php echo $preference['lng']; ?></p>
                            </div>
                            <button type="button" onclick="confirmDeleteAddress('<?php echo htmlspecialchars(addslashes($preference['type'])); ?>')" class="btn-delete px-4 py-2 text-sm font-bold text-white rounded-lg hover:shadow-md transition">
                                Eliminar
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form action="" method="POST" class="mt-6">
                    <?php echo csrfField(); ?>
                    <input type="text" name="type" placeholder="Nombre de la direccion (Hogar, Trabajo...)" class="w-full px-4 py-2 border rounded-lg mb-2" required>
                    <input type="text" name="address" placeholder="Direccion" class="w-full px-4 py-2 border rounded-lg mb-2" required>
                    <button type="submit" class="btn-add w-full text-white py-3 rounded-lg font-bold hover:shadow-md transition">
                        Agregar Direccion
                    </button>
                </form>
            </section>

            <!-- Eliminar cuenta -->
            <section class="mt-8 pt-6 border-t border-gray-700">
                <h3 class="text-lg font-bold text-red-400 mb-2">Zona de peligro</h3>
                <p class="text-sm text-gray-400 mb-4">Al eliminar tu cuenta se borraran todos tus datos y direcciones guardadas permanentemente.</p>
                <button type="button" onclick="confirmDeleteAccount()" class="btn-delete w-full text-white py-3 rounded-lg font-bold hover:shadow-md transition">
                    Eliminar mi cuenta
                </button>
            </section>
        </main>

        <nav class="bg-gray-800 shadow-inner fixed bottom-0 left-0 w-full flex justify-around py-2">
            <a href="dashboardpa.php" class="text-center text-indigo-400 hover:text-indigo-600">
                <i class="fas fa-home text-2xl"></i>
                <p class="text-xs">Inicio</p>
            </a>
            <a href="historial_pasajero.php" class="text-center text-indigo-400 hover:text-indigo-600">
                <i class="fas fa-history text-2xl"></i>
                <p class="text-xs">Historial</p>
            </a>
        </nav>
    </div>

    <script src="modal.js"></script>
    <script>
        function previewAndSubmitPhoto(event) {
            const file = event.target.files[0];
            if (!file) return;
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                RideIt.alert({ title: 'Formato no valido', message: 'Solo se permiten imagenes JPG, PNG, GIF o WebP.', type: 'danger' });
                event.target.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                RideIt.alert({ title: 'Archivo muy grande', message: 'La imagen no debe superar 5MB.', type: 'danger' });
                event.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function() {
                document.getElementById('profile-pic').src = reader.result;
                RideIt.confirm({
                    title: 'Actualizar foto',
                    message: '¿Deseas guardar esta foto como tu foto de perfil?',
                    type: 'info',
                    confirmText: 'Guardar',
                    confirmClass: 'btn-success',
                    onConfirm: () => document.getElementById('photo-form').submit(),
                    onCancel: () => {
                        event.target.value = '';
                        document.getElementById('profile-pic').src = '<?php echo htmlspecialchars($userPhoto); ?>';
                    }
                });
            };
            reader.readAsDataURL(file);
        }

        function confirmDeleteAddress(type) {
            RideIt.confirm({
                title: 'Eliminar direccion',
                message: '¿Deseas eliminar la direccion <strong>"' + type + '"</strong>? Esta accion no se puede deshacer.',
                type: 'danger',
                confirmText: 'Eliminar',
                onConfirm: () => window.location.href = '?delete=' + encodeURIComponent(type) + '&token=<?php echo urlencode($_SESSION['csrf_token'] ?? ''); ?>'
            });
        }

        function confirmDeleteAccount() {
            RideIt.confirm({
                title: 'Eliminar cuenta',
                message: 'Esta accion es <strong>irreversible</strong>. Se eliminaran todos tus datos personales y direcciones guardadas. ¿Deseas continuar?',
                type: 'danger',
                confirmText: 'Si, continuar',
                onConfirm: () => {
                    RideIt.prompt({
                        title: 'Confirmar eliminacion',
                        message: 'Ingresa tu contraseña actual para confirmar.',
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
