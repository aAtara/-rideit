<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido - RideIt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(135deg, #01579B 0%, #003366 100%); }
        .fade-in { animation: fadeIn 1s; }
        @keyframes fadeIn { from { opacity: 0;} to { opacity: 1;} }
    </style>
    <script>
        // Redirige y recarga la página del dashboard después de 1.3 segundos
        setTimeout(function() {
            window.location.replace("dashboardpa.php");
        }, 1300);
    </script>
</head>
<body class="min-h-screen flex flex-col justify-center items-center">
    <div class="fade-in flex flex-col justify-center items-center w-full px-4">
        <div class="glass w-full max-w-sm mx-auto p-8 flex flex-col items-center shadow-2xl">
            <div class="login-icon mb-4 w-16 h-16 rounded-full flex items-center justify-center shadow-lg animate-bounce bg-blue-500 text-white text-3xl">
                🚖
            </div>
            <h1 class="text-3xl font-extrabold text-white mb-2 text-center tracking-tight drop-shadow-lg">
                ¡Bienvenido!
            </h1>
            <p class="text-blue-200 mb-3 text-center">Redirigiendo a tu panel…</p>
            <div class="animate-spin mt-3 border-4 border-blue-400 border-t-transparent rounded-full w-8 h-8"></div>
        </div>
    </div>
</body>
</html>