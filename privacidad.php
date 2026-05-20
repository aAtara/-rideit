<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso de Privacidad - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body { background: linear-gradient(135deg, #01579B 0%, #003366 100%); }
        .glass { background: rgba(23, 37, 84, 0.72); backdrop-filter: blur(5px); border-radius: 1.25rem; border: 1px solid rgba(255, 255, 255, 0.10); }
    </style>
</head>
<body class="min-h-screen text-gray-300">

    <header class="bg-gradient-to-r from-blue-800 to-indigo-900 text-white px-4 py-3 flex justify-between items-center shadow-lg">
        <h1 class="text-xl font-bold">Aviso de Privacidad</h1>
        <a href="javascript:history.back()" class="bg-white text-blue-600 px-4 py-2 rounded-lg text-sm font-bold shadow-md hover:shadow-lg">
            Volver
        </a>
    </header>

    <main class="p-4 max-w-2xl mx-auto">
        <div class="glass p-6 space-y-4">
            <h2 class="text-2xl font-bold text-white">Aviso de Privacidad - RideIt</h2>
            <p class="text-sm text-gray-400">Ultima actualizacion: Mayo 2026</p>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">1. Responsable del tratamiento</h3>
                <p class="text-sm">RideIt, con domicilio en Cd. Delicias, Chihuahua, Mexico, es responsable del tratamiento de tus datos personales.</p>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">2. Datos que recopilamos</h3>
                <ul class="text-sm list-disc list-inside space-y-1">
                    <li>Nombre completo</li>
                    <li>Correo electronico</li>
                    <li>Numero de telefono</li>
                    <li>Ubicacion GPS (solo durante el uso activo de la aplicacion)</li>
                    <li>Historial de viajes</li>
                    <li>Calificaciones y comentarios</li>
                </ul>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">3. Finalidad del tratamiento</h3>
                <p class="text-sm">Tus datos personales seran utilizados para:</p>
                <ul class="text-sm list-disc list-inside space-y-1">
                    <li>Crear y administrar tu cuenta de usuario</li>
                    <li>Conectarte con conductores disponibles</li>
                    <li>Calcular rutas, tarifas y tiempos estimados de llegada</li>
                    <li>Garantizar la seguridad durante los viajes</li>
                    <li>Enviar notificaciones sobre el estado de tus viajes</li>
                    <li>Mejorar la calidad del servicio</li>
                </ul>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">4. Uso de la ubicacion</h3>
                <p class="text-sm">RideIt solicita permiso para acceder a la ubicacion de tu dispositivo. Este permiso es necesario para mostrar tu posicion en el mapa y calcular rutas. Tu ubicacion solo se utiliza mientras la aplicacion esta activa y no se almacena de forma permanente.</p>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">5. Proteccion de datos</h3>
                <p class="text-sm">Implementamos medidas de seguridad tecnicas y administrativas para proteger tus datos personales, incluyendo:</p>
                <ul class="text-sm list-disc list-inside space-y-1">
                    <li>Cifrado de contrasenas</li>
                    <li>Conexiones seguras (HTTPS)</li>
                    <li>Tokens de seguridad contra ataques CSRF</li>
                    <li>Acceso restringido a la base de datos</li>
                </ul>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">6. Derechos ARCO</h3>
                <p class="text-sm">Tienes derecho a Acceder, Rectificar, Cancelar u Oponerte al tratamiento de tus datos personales. Para ejercer estos derechos, contactanos a traves de la seccion de Ayuda en la aplicacion.</p>
            </section>

            <section class="space-y-2">
                <h3 class="text-lg font-bold text-blue-300">7. Contacto</h3>
                <p class="text-sm">Para cualquier duda sobre este aviso de privacidad, contactanos en: soporte@rideit.com</p>
            </section>
        </div>
    </main>

    <footer class="bg-black text-gray-300 text-center py-6 mt-8 w-full">
        <p class="text-sm">&copy; 2025 RideIt. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
