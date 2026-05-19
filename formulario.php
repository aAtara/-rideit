<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Aplicacion - Conductor Uber</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
</head>
<body class="bg-black text-white font-sans">

<?php
require_once 'config.php';
include 'csrf.php';
session_start();

$conn = new mysqli(DB_HOST_REMOTE, DB_USER_REMOTE, DB_PASS_REMOTE, DB_NAME_REMOTE);

if ($conn->connect_error) {
    die("Error de conexion.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        echo "<div class='text-center p-4 mb-4 bg-red-500 text-white rounded-lg'>Token de seguridad invalido.</div>";
    } else {
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $own_car = trim($_POST['own_car']);
        $car_details = trim($_POST['car_details']);
        $car_year = trim($_POST['car_year']);
        $license = trim($_POST['license']);
        $trustworthy = trim($_POST['trustworthy']);
        $reliability = trim($_POST['reliability']);

        $stmt = $conn->prepare("INSERT INTO applications (full_name, phone, email, own_car, car_details, car_year, license, trustworthy, reliability) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $full_name, $phone, $email, $own_car, $car_details, $car_year, $license, $trustworthy, $reliability);

        if ($stmt->execute()) {
            echo "<div class='text-center p-4 mb-4 bg-green-500 text-white rounded-lg'>Aplicacion enviada exitosamente!</div>";
        } else {
            echo "<div class='text-center p-4 mb-4 bg-red-500 text-white rounded-lg'>Error al enviar la aplicacion.</div>";
        }

        $stmt->close();
    }
}

$conn->close();
?>

    <div class="flex flex-col min-h-screen justify-center items-center px-4">
        <h1 class="text-4xl font-extrabold text-white mb-6 text-center">
            Formulario de Aplicacion - <span class="text-blue-400">Conductor Uber</span>
        </h1>

        <form action="" method="POST" class="bg-gradient-to-r from-gray-800 via-gray-900 to-black p-6 rounded-lg shadow-lg w-full max-w-lg">
            <?php echo csrfField(); ?>
            <h2 class="text-2xl font-bold text-white mb-4 text-center">Informacion Personal</h2>

            <div class="mb-4">
                <label for="full_name" class="block text-sm font-medium text-gray-300 mb-2">Nombre Completo</label>
                <input type="text" name="full_name" id="full_name" placeholder="Ingresa tu nombre completo" required class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white">
            </div>

            <div class="mb-4">
                <label for="phone" class="block text-sm font-medium text-gray-300 mb-2">Numero de Telefono</label>
                <input type="tel" name="phone" id="phone" placeholder="Ingresa tu numero de telefono" required pattern="[0-9]{7,15}" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white">
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Correo Electronico</label>
                <input type="email" name="email" id="email" placeholder="Ingresa tu correo electronico" required class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white">
            </div>

            <div class="mb-4">
                <label for="own_car" class="block text-sm font-medium text-gray-300 mb-2">Tienes auto propio?</label>
                <select name="own_car" id="own_car" required class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white">
                    <option value="">Selecciona una opcion</option>
                    <option value="yes">Si</option>
                    <option value="no">No</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="car_details" class="block text-sm font-medium text-gray-300 mb-2">Marca y Modelo del Auto</label>
                <input type="text" name="car_details" id="car_details" placeholder="Ingresa la marca y modelo de tu auto" required class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white">
            </div>

            <div class="mb-4">
                <label for="car_year" class="block text-sm font-medium text-gray-300 mb-2">Ano del Auto</label>
                <input type="number" name="car_year" id="car_year" placeholder="Ingresa el ano del auto" required min="2000" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white">
            </div>

            <div class="mb-4">
                <label for="license" class="block text-sm font-medium text-gray-300 mb-2">Tienes licencia de conducir vigente?</label>
                <select name="license" id="license" required class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white">
                    <option value="">Selecciona una opcion</option>
                    <option value="yes">Si</option>
                    <option value="no">No</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="trustworthy" class="block text-sm font-medium text-gray-300 mb-2">Por que crees que serias un buen conductor de Uber?</label>
                <textarea name="trustworthy" id="trustworthy" placeholder="Escribe tu respuesta aqui" required class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white"></textarea>
            </div>

            <div class="mb-4">
                <label for="reliability" class="block text-sm font-medium text-gray-300 mb-2">Como describirias tu nivel de responsabilidad?</label>
                <textarea name="reliability" id="reliability" placeholder="Escribe tu respuesta aqui" required class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white"></textarea>
            </div>

            <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-lg font-bold hover:bg-blue-600 transition">
                Enviar Aplicacion
            </button>
        </form>
    </div>

    <footer class="bg-black text-gray-300 text-center py-6 mt-8">
        <p class="text-sm">&copy; 2025 RideIt. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
