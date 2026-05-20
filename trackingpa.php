<?php
include 'db.php';
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

$tripId = $_GET['trip_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$tripId) {
    die("ID de viaje no especificado.");
}

$stmt = $conn->prepare("
    SELECT pickup_address, destination_address, distance, fare, status
    FROM trips WHERE id = ? AND passenger_id = ?
");
$stmt->bind_param("ii", $tripId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$trip = $result->fetch_assoc();

if (!$trip) {
    die("Viaje no encontrado o no tienes acceso.");
}

$mapsApiKey = GOOGLE_MAPS_API_KEY;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento del Viaje</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($mapsApiKey); ?>&libraries=places"></script>
    <style>
      body { background: #1e293b; color: #f8fafc; }
      #map { width: 100%; height: 400px; border-radius: 8px; }
      .bg-white { background-color: #334155; color: #f8fafc; }
      header { background: linear-gradient(to right, #22c55e, #3b82f6); }
      .text-indigo-600 { color: #3b82f6; }
      .text-green-600 { color: #22c55e; }
      .notification {
          background-color: rgba(0, 0, 0, 0.8); color: #f8fafc; padding: 10px;
          position: fixed; bottom: 20px; right: 20px; border-radius: 5px;
          z-index: 1000; display: none; box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
      }
      input, select {
          background-color: #1e293b; color: #f8fafc;
          border: 1px solid #475569; border-radius: 8px; padding: 0.5rem;
      }
      input::placeholder { color: #94a3b8; opacity: 0.7; }
      input:focus, select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px #3b82f6; }
    </style>
</head>
<body class="font-sans">

    <div class="flex flex-col min-h-screen">
        <header class="bg-gradient-to-r from-green-600 to-blue-600 text-white px-4 py-3 flex justify-between items-center shadow-md">
            <h1 class="text-xl font-bold">Seguimiento del Viaje</h1>
        </header>

        <main class="flex-1 p-4 space-y-6">
            <div id="map"></div>
            <div class="bg-white p-4 rounded-xl shadow-md space-y-4">
                <h2 class="text-lg font-bold">Detalles del Viaje</h2>
                <p id="status" class="text-sm text-indigo-600 font-bold">
                    <?php echo htmlspecialchars($trip['status'] === 'asignado' ? 'El conductor esta en camino.' : 'Esperando a que un conductor tome su solicitud...'); ?>
                </p>
                <p id="pickup" class="text-sm"><strong>Recoger en:</strong> <?php echo htmlspecialchars($trip['pickup_address'] ?? 'No disponible'); ?></p>
                <p id="destination" class="text-sm"><strong>Destino:</strong> <?php echo htmlspecialchars($trip['destination_address'] ?? 'No disponible'); ?></p>
                <p id="distance" class="text-sm"><strong>Distancia:</strong> <?php echo htmlspecialchars($trip['distance'] ?? '0'); ?> km</p>
                <p id="fare" class="text-sm"><strong>Tarifa:</strong> $<?php echo htmlspecialchars($trip['fare'] ?? '0.00'); ?></p>
                <p id="driver" class="text-sm text-green-600"><strong>Conductor:</strong> Esperando asignacion...</p>
                <p id="driverPlate" class="text-sm"><strong>Placa:</strong> N/A</p>

                <!-- Boton de Panico SOS -->
                <button id="sos-button" onclick="activarSOS()" class="w-full bg-red-600 text-white py-3 rounded-lg font-bold hover:bg-red-700 transition mt-4 text-lg shadow-lg">
                    SOS - Boton de Panico
                </button>
                <div id="sos-confirm" style="display:none;" class="bg-red-900/80 p-4 rounded-lg mt-2 text-center">
                    <p class="text-white font-bold mb-2">Estas seguro de enviar una alerta de emergencia?</p>
                    <button onclick="enviarSOS()" class="bg-red-500 text-white px-6 py-2 rounded-lg font-bold mr-2 hover:bg-red-600">Si, enviar alerta</button>
                    <button onclick="cancelarSOS()" class="bg-gray-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-gray-700">Cancelar</button>
                </div>
                <div id="sos-sent" style="display:none;" class="bg-green-700/80 p-4 rounded-lg mt-2 text-center">
                    <p class="text-white font-bold">Alerta enviada. La central ha sido notificada.</p>
                    <p class="text-gray-300 text-sm mt-1">Tu ubicacion GPS ha sido compartida con el equipo de seguridad.</p>
                </div>
            </div>
        </main>

        <div class="notification" id="notification">Notificacion</div>
    </div>

    <script>
        let map, driverMarker, previousStatus = null;

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), { center: { lat: 28.1973, lng: -105.4702 }, zoom: 14 });
            driverMarker = new google.maps.Marker({ map, title: "Conductor", icon: "http://maps.google.com/mapfiles/ms/icons/taxi.png" });
            updateTripStatus();
        }

        function updateTripStatus() {
            const tripId = <?php echo json_encode($tripId); ?>;
            fetch(`trip_status.php?trip_id=${tripId}`)
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === "asignado") {
                        document.getElementById("status").textContent = "El conductor esta en camino.";
                        document.getElementById("driver").textContent = `Conductor: ${data.driver_name}`;
                        document.getElementById("driverPlate").textContent = `Placa: ${data.driver_plate}`;
                        const driverLocation = data.driver_location;
                        if (driverLocation) {
                            driverMarker.setPosition(driverLocation);
                            map.setCenter(driverLocation);
                        }
                    }
                    if (data.status !== previousStatus) {
                        if (data.status === "afuera") {
                            showNotification("El conductor esta afuera.");
                        } else if (data.status === "completado") {
                            showNotification("El viaje ha finalizado. Redirigiendo a calificar...");
                            setTimeout(() => { window.location.href = "calificar_viaje.php?trip_id=" + <?php echo json_encode($tripId); ?>; }, 3000);
                        }
                        previousStatus = data.status;
                    }
                })
                .catch((error) => console.error("Error al actualizar el estado del viaje:", error));
            setTimeout(updateTripStatus, 5000);
        }

        function showNotification(message) {
            const notification = document.getElementById("notification");
            notification.textContent = message;
            notification.style.display = "block";
            setTimeout(() => notification.style.display = "none", 5000);
        }

        function activarSOS() {
            document.getElementById('sos-confirm').style.display = 'block';
        }

        function cancelarSOS() {
            document.getElementById('sos-confirm').style.display = 'none';
        }

        function enviarSOS() {
            document.getElementById('sos-confirm').style.display = 'none';
            document.getElementById('sos-button').style.display = 'none';

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const tripId = <?php echo json_encode($tripId); ?>;
                    fetch('sos_alert.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            trip_id: tripId,
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        })
                    }).then(() => {
                        document.getElementById('sos-sent').style.display = 'block';
                    });
                }, function() {
                    document.getElementById('sos-sent').style.display = 'block';
                });
            } else {
                document.getElementById('sos-sent').style.display = 'block';
            }
        }

        window.onload = initMap;
    </script>
</body>
</html>
