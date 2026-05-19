<?php
include 'db.php';
include 'csrf.php';
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = "Token de seguridad invalido. Recarga la pagina.";
    } else {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $plate = trim($_POST['plate']);
    $description = trim($_POST['description']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($email) || empty($phone) || empty($plate) || empty($password)) {
        $error = "Por favor, completa todos los campos obligatorios.";
    } elseif (!preg_match('/^[0-9]{7,15}$/', $phone)) {
        $error = "Numero de telefono invalido.";
    } elseif (strlen($password) < 6) {
        $error = "La contrasena debe tener al menos 6 caracteres.";
    } else {
        // Verificar si el correo ya está registrado
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Este correo ya está registrado.";
        } else {
            // Registrar nuevo conductor
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, plate, description, password, role) VALUES (?, ?, ?, ?, ?, ?, 'conductor')");
            $stmt->bind_param("ssssss", $name, $email, $phone, $plate, $description, $hashedPassword);

            if ($stmt->execute()) {
                header("Location: login_pasajero.php?success=registered");
                exit;
            } else {
                $error = "Hubo un error al registrar al conductor. Intenta de nuevo.";
            }
        }
    }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Conductores</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        /* Estilos personalizados */
        body {
            background: linear-gradient(to right, #1f2937, #3b4252);
            font-family: 'Inter', sans-serif;
        }

        .register-box {
            background: rgba(31, 41, 55, 0.9);
            backdrop-filter: blur(10px);
        }

        .register-box input, .register-box textarea {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .register-box input::placeholder, .register-box textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .register-box input:focus, .register-box textarea:focus {
            outline: none;
            border-color: #60a5fa;
            background: rgba(255, 255, 255, 0.2);
        }

        .error-box {
            background: rgba(239, 68, 68, 0.9);
        }
    </style>
</head>
<body class="text-gray-300">

    <!-- Contenedor principal -->
    <div class="flex flex-col min-h-screen justify-center items-center px-4">

        <!-- Título -->
        <h1 class="text-4xl font-extrabold text-white mb-6 text-center">
            🚖 Registra un <span class="text-green-400">Conductor</span>
        </h1>

        <!-- Mostrar errores -->
        <?php if (!empty($error)): ?>
            <div class="error-box text-center p-4 rounded-lg mb-4 text-white shadow-md">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de registro -->
        <form action="" method="POST" class="register-box p-6 rounded-lg shadow-lg w-full max-w-md space-y-4">
            <?php echo csrfField(); ?>
            <h2 class="text-2xl font-bold text-white mb-4 text-center">Crear Cuenta de Conductor</h2>

            <!-- Nombre -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Nombre Completo</label>
                <input type="text" id="name" name="name" placeholder="Ingresa tu nombre" required class="w-full px-4 py-2 border border-gray-600 rounded-lg">
            </div>

            <!-- Correo Electrónico -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Correo Electrónico</label>
                <input type="email" id="email" name="email" placeholder="Ingresa tu correo" required class="w-full px-4 py-2 border border-gray-600 rounded-lg">
            </div>

            <!-- Número de Teléfono -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-300 mb-1">Número de Teléfono</label>
                <input type="text" id="phone" name="phone" placeholder="Ingresa tu número de teléfono" required class="w-full px-4 py-2 border border-gray-600 rounded-lg">
            </div>

            <!-- Placa del Vehículo -->
            <div>
                <label for="plate" class="block text-sm font-medium text-gray-300 mb-1">Placa del Vehículo</label>
                <input type="text" id="plate" name="plate" placeholder="Ingresa la placa del vehículo" required class="w-full px-4 py-2 border border-gray-600 rounded-lg">
            </div>

            <!-- Descripción -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-300 mb-1">Descripción (opcional)</label>
                <textarea id="description" name="description" rows="3" placeholder="Breve descripción..." class="w-full px-4 py-2 border border-gray-600 rounded-lg"></textarea>
            </div>

            <!-- Contraseña -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="Crea una contraseña" required class="w-full px-4 py-2 border border-gray-600 rounded-lg">
            </div>

            <!-- Botón de registro -->
            <button type="submit" class="w-full bg-green-500 text-white py-3 rounded-lg font-bold hover:bg-green-600 transition">
                Registrar Conductor
            </button>
        </form>

        <!-- Enlace a iniciar sesión -->
        <p class="mt-4 text-sm text-gray-400">
            ¿Ya tienes una cuenta? <a href="login.html" class="text-blue-400 hover:underline">Inicia Sesión</a>
        </p>
    </div>

    <!-- Footer -->
    <footer class="text-center text-sm mt-8 text-gray-400">
        © 2025 TuApp. Todos los derechos reservados.
    </footer>
</body>
</html>
