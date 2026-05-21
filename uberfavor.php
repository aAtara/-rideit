<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UberFavor - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(135deg, #01579B 0%, #003366 100%); }
        .glass { background: rgba(23, 37, 84, 0.72); backdrop-filter: blur(5px); border-radius: 1.25rem; border: 1px solid rgba(255, 255, 255, 0.10); }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-center items-center text-gray-300">

    <header class="fixed top-0 w-full bg-gradient-to-r from-blue-800 to-indigo-900 text-white px-4 py-3 flex justify-between items-center shadow-lg z-50">
        <h1 class="text-xl font-bold">UberFavor</h1>
        <a href="dashboardpa.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg text-sm font-bold shadow-md hover:shadow-lg">
            Volver
        </a>
    </header>

    <div class="glass w-full max-w-md mx-auto p-8 flex flex-col items-center shadow-2xl mt-20">
        <span class="text-6xl mb-4">🎁</span>
        <h2 class="text-2xl font-extrabold text-white mb-2 text-center">UberFavor</h2>
        <p class="text-blue-200 mb-4 text-center text-sm">Pide ayuda con cualquier tarea o recado</p>

        <div class="bg-yellow-500/20 border border-yellow-500/50 rounded-xl p-4 w-full text-center mb-6">
            <p class="text-yellow-300 font-bold text-sm">Proximamente</p>
            <p class="text-gray-400 text-xs mt-1">Esta funcion estara disponible en la proxima actualizacion de RideIt.</p>
        </div>

        <div class="w-full space-y-3 text-sm">
            <div class="bg-gray-800/50 p-3 rounded-lg flex items-center gap-3">
                <span class="text-xl">📦</span>
                <div>
                    <p class="font-bold text-white">Envio de paquetes</p>
                    <p class="text-gray-400 text-xs">Envia y recibe paquetes en la ciudad</p>
                </div>
            </div>
            <div class="bg-gray-800/50 p-3 rounded-lg flex items-center gap-3">
                <span class="text-xl">📋</span>
                <div>
                    <p class="font-bold text-white">Tramites y recados</p>
                    <p class="text-gray-400 text-xs">Alguien hace tus recados por ti</p>
                </div>
            </div>
            <div class="bg-gray-800/50 p-3 rounded-lg flex items-center gap-3">
                <span class="text-xl">🏥</span>
                <div>
                    <p class="font-bold text-white">Recoger medicinas</p>
                    <p class="text-gray-400 text-xs">Recogemos tus medicinas en la farmacia</p>
                </div>
            </div>
        </div>

        <a href="dashboardpa.php" class="mt-6 w-full bg-gradient-to-r from-blue-600 to-blue-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-center block">
            Volver al inicio
        </a>
    </div>

    <footer class="fixed bottom-0 w-full bg-black text-gray-400 text-center py-4 text-xs">
        &copy; 2025 RideIt. Todos los derechos reservados.
    </footer>
</body>
</html>
