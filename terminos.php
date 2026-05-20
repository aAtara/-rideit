<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminos y Condiciones - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(135deg, #01579B 0%, #003366 100%); }
        .glass { background: rgba(23, 37, 84, 0.72); backdrop-filter: blur(5px); border-radius: 1.25rem; border: 1px solid rgba(255, 255, 255, 0.10); }
    </style>
</head>
<body class="min-h-screen text-gray-300">

    <header class="bg-gradient-to-r from-blue-800 to-indigo-900 text-white px-4 py-3 flex justify-between items-center shadow-lg">
        <h1 class="text-xl font-bold">Terminos y Condiciones</h1>
        <a href="javascript:history.back()" class="bg-white text-blue-600 px-4 py-2 rounded-lg text-sm font-bold shadow-md hover:shadow-lg">
            Volver
        </a>
    </header>

    <main class="p-4 max-w-2xl mx-auto">
        <div class="glass p-6 space-y-4">
            <h2 class="text-2xl font-bold text-white">Terminos y Condiciones de Uso</h2>
            <p class="text-sm text-gray-400">Ultima actualizacion: Mayo 2026</p>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">1. Aceptacion de los terminos</h3>
                <p class="text-sm">Al registrarte y utilizar RideIt, aceptas estos terminos y condiciones. Si no estas de acuerdo, no utilices la plataforma.</p>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">2. Descripcion del servicio</h3>
                <p class="text-sm">RideIt es una plataforma que conecta pasajeros con conductores para servicios de transporte. RideIt no es una empresa de transporte, sino un intermediario tecnologico.</p>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">3. Requisitos de registro</h3>
                <ul class="text-sm list-disc list-inside space-y-1">
                    <li>Ser mayor de 18 anios</li>
                    <li>Proporcionar datos veridicos y actualizados</li>
                    <li>Mantener la confidencialidad de tu cuenta</li>
                </ul>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">4. Tarifas y pagos</h3>
                <p class="text-sm">Las tarifas se calculan automaticamente con base en la distancia del recorrido. La tarifa mostrada al momento de solicitar el viaje es estimada y puede variar si se modifica el destino.</p>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">5. Cancelaciones</h3>
                <p class="text-sm">El pasajero puede cancelar un viaje sin costo antes de que el conductor llegue al punto de recogida. Cancelaciones frecuentes pueden resultar en restricciones de la cuenta.</p>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">6. Conducta del usuario</h3>
                <p class="text-sm">Los usuarios se comprometen a tratar con respeto a conductores y demas usuarios. Cualquier comportamiento inapropiado podra resultar en la suspension de la cuenta.</p>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">7. Seguridad</h3>
                <p class="text-sm">RideIt cuenta con un boton de panico (SOS) disponible durante los viajes activos. En caso de emergencia, la plataforma enviara una alerta con tu ubicacion.</p>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">8. Contacto</h3>
                <p class="text-sm">Para dudas o aclaraciones, utiliza la seccion de Ayuda dentro de la aplicacion o escribe a soporte@rideit.com</p>
            </section>
        </div>
    </main>

    <footer class="bg-black text-gray-300 text-center py-6 mt-8 w-full">
        <p class="text-sm">&copy; 2025 RideIt. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
