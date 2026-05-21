<?php
include 'db.php';
include 'csrf.php';
session_start();

$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = "Token de seguridad invalido. Recarga la pagina.";
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Ingresa un correo electronico valido.";
        } else {
            // Verificar si el email existe
            $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            // Siempre mostrar mensaje de exito (para no revelar si el email existe o no)
            $mensaje = "Si el correo esta registrado, recibiras instrucciones para restablecer tu contrasena. Revisa tu bandeja de entrada y la carpeta de spam.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contrasena - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(135deg, #01579B 0%, #003366 100%); }
        .glass { background: rgba(23, 37, 84, 0.72); backdrop-filter: blur(5px); border-radius: 1.25rem; border: 1px solid rgba(255, 255, 255, 0.10); }
        .input:focus { outline: 2px solid #29B6F6; border-color: #29B6F6; }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-center items-center text-gray-300">

    <div class="glass w-full max-w-sm mx-auto p-8 flex flex-col items-center shadow-2xl">
        <div class="mb-4 w-16 h-16 rounded-full flex items-center justify-center shadow-lg bg-gradient-to-r from-blue-500 to-blue-400 text-3xl">
            🔑
        </div>
        <h1 class="text-2xl font-extrabold text-white mb-2 text-center">Recuperar Contrasena</h1>
        <p class="text-blue-200 mb-6 text-center text-sm">Ingresa tu correo y te enviaremos instrucciones para restablecer tu contrasena.</p>

        <?php if ($mensaje): ?>
            <div class="bg-green-500/90 text-center p-3 rounded-lg mb-4 text-white shadow-md w-full">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
            <a href="login_pasajero.php" class="w-full bg-gradient-to-r from-blue-600 to-blue-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-center block mt-2">
                Volver al login
            </a>
        <?php elseif ($error): ?>
            <div class="bg-red-500/90 text-center p-3 rounded-lg mb-4 text-white shadow-md w-full">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($mensaje)): ?>
        <form action="recuperar_password.php" method="POST" class="w-full flex flex-col gap-4">
            <?php echo csrfField(); ?>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-200 mb-1">Correo electronico</label>
                <input type="email" name="email" id="email" placeholder="correo@ejemplo.com" required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    class="input w-full px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white transition" />
            </div>
            <button type="submit"
                class="w-full bg-gradient-to-r from-blue-600 to-blue-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-lg mt-2">
                Enviar instrucciones
            </button>
        </form>

        <div class="mt-6 w-full text-center">
            <a href="login_pasajero.php" class="text-blue-300 hover:text-blue-400 text-sm transition">
                Volver al inicio de sesion
            </a>
        </div>
        <?php endif; ?>
    </div>

    <footer class="bg-black text-gray-300 text-center py-6 mt-8 w-full">
        <p class="text-sm">&copy; 2025 RideIt. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
