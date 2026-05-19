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
$stmt = $conn->prepare("SELECT name, description, photo, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userName = $user['name'];
    $userDescription = $user['description'] ?? "Pasajero desde " . date("Y");
    $userPhoto = $user['photo'] ?? "https://via.placeholder.com/150";
    $userPhone = $user['phone'] ?? "No registrado";
} else {
    session_destroy();
    header("Location: login_pasajero.php");
    exit;
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

if (isset($_GET['delete'])) {
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
    <title>Perfil del Pasajero - TuApp</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
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
            <h1 class="text-xl font-bold">TuApp</h1>
            <a href="dashboardpa.php" class="bg-indigo-500 px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-600 transition">
                Volver
            </a>
        </header>

        <main class="flex-1 p-6">
            <section class="card p-6 rounded-xl shadow-md text-center">
                <div class="relative">
                    <img id="profile-pic" src="<?php echo htmlspecialchars($userPhoto); ?>" alt="Foto de perfil" class="profile-pic w-32 h-32 rounded-full mx-auto mb-4">
                </div>
                <h2 id="profile-name" class="text-2xl font-bold text-white"><?php echo htmlspecialchars($userName); ?></h2>
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
                            <a href="?delete=<?php echo urlencode($preference['type']); ?>" class="btn-delete px-4 py-2 text-sm font-bold text-white rounded-lg hover:shadow-md transition">
                                Eliminar
                            </a>
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
        </main>

        <nav class="bg-gray-800 shadow-inner fixed bottom-0 left-0 w-full flex justify-around py-2">
            <a href="dashboardpa.php" class="text-center text-indigo-400 hover:text-indigo-600">
                <i class="fas fa-home text-2xl"></i>
                <p class="text-xs">Inicio</p>
            </a>
            <a href="history.php" class="text-center text-indigo-400 hover:text-indigo-600">
                <i class="fas fa-history text-2xl"></i>
                <p class="text-xs">Historial</p>
            </a>
        </nav>
    </div>
</body>
</html>
