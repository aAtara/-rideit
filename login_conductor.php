<?php
include 'csrf.php';
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RideIt Conductores</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); }
        .glass { background: rgba(23, 37, 84, 0.72); backdrop-filter: blur(5px); border-radius: 1.25rem; border: 1px solid rgba(255, 255, 255, 0.10); }
        input:focus { outline: 2px solid #22c55e; border-color: #22c55e; }
    </style>
</head>
<body class="text-white font-sans min-h-screen flex flex-col justify-center items-center">

    <div class="glass w-full max-w-sm mx-auto p-8 flex flex-col items-center shadow-2xl">
        <div class="mb-4 w-16 h-16 rounded-full flex items-center justify-center shadow-lg bg-gradient-to-r from-green-500 to-green-400 text-3xl">
            🚗
        </div>
        <h1 class="text-3xl font-extrabold text-white mb-2 text-center">Conductor</h1>
        <p class="text-green-200 mb-6 text-center">Inicia sesion en <span class="font-bold text-green-400">RideIt</span></p>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
            <div class="bg-green-500/90 text-center p-3 rounded-lg mb-4 text-white shadow-md w-full">
                Registro exitoso. Inicia sesion con tus credenciales.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-500/90 text-center p-3 rounded-lg mb-4 text-white shadow-md w-full">
                <?php
                $err = $_GET['error'];
                if ($err === 'empty_fields') echo "Por favor, completa todos los campos.";
                elseif ($err === 'user_not_found') echo "Usuario no encontrado. Verifica tu correo.";
                elseif ($err === 'invalid_password') echo "Contrasena incorrecta. Intentalo de nuevo.";
                elseif ($err === 'invalid_token') echo "Token de seguridad invalido. Recarga la pagina.";
                else echo "Error desconocido.";
                ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="w-full flex flex-col gap-4">
            <?php echo csrfField(); ?>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-200 mb-1">Correo electronico</label>
                <input type="email" name="email" id="email" placeholder="correo@ejemplo.com" required
                    class="w-full px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white transition">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-200 mb-1">Contrasena</label>
                <input type="password" name="password" id="password" placeholder="********" required
                    class="w-full px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white transition">
            </div>
            <button type="submit"
                class="w-full bg-gradient-to-r from-green-600 to-green-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-lg mt-2">
                Iniciar Sesion
            </button>
        </form>

        <div class="mt-6 w-full">
            <a href="registrocon.php"
               class="block w-full bg-gradient-to-r from-blue-500 to-blue-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-center">
                No tienes cuenta? Registrate como conductor
            </a>
        </div>
    </div>

    <footer class="text-gray-400 text-center py-6 mt-8 w-full">
        <p class="text-sm">&copy; 2025 RideIt. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
