<?php
// Incluir la conexión a la base de datos
include 'db.php';
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

// Obtener el ID del viaje
$tripId = $_GET['trip_id'] ?? null;

// Verificar si el ID del viaje es válido
if (!$tripId) {
    die("ID de viaje no especificado.");
}

// Obtener detalles del viaje
$stmt = $conn->prepare("
    SELECT pickup_address, destination_address, distance, fare, status 
    FROM trips WHERE id = ?
");
$stmt->bind_param("i", $tripId);
$stmt->execute();
$result = $stmt->get_result();
$trip = $result->fetch_assoc();

// Manejar el caso donde no se encuentra el viaje
if (!$trip) {
    die("Viaje no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento del Viaje</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA3xKp9oCPeRfduSHv29G_nph7u4rLHQVI&libraries=places"></script>
    <style>
      <style>
      body {
    background: #1e293b; /* Fondo oscuro */
    color: #f8fafc; /* Texto claro */
}

#map {
    width: 100%;
    height: 400px;
    border-radius: 8px;
}

.bg-white {
    background-color: #334155; /* Fondo oscuro para tarjetas y secciones */
    color: #f8fafc; /* Texto claro */
}

header {
    background: linear-gradient(to right, #22c55e, #3b82f6); /* Gradiente verde a azul */
}

.text-indigo-600 {
    color: #3b82f6; /* Azul claro */
}

.text-green-600 {
    color: #22c55e; /* Verde claro */
}

.notification {
    background-color: rgba(0, 0, 0, 0.8); /* Fondo negro semitransparente */
    color: #f8fafc; /* Texto blanco */
    padding: 10px;
    position: fixed;
    bottom: 20px;
    right: 20px;
    border-radius: 5px;
    z-index: 1000;
    display: none;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
}

input, select {
    background-color: #1e293b; /* Fondo oscuro */
    color: #f8fafc; /* Texto claro */
    border: 1px solid #475569; /* Borde gris */
    border-radius: 8px;
    padding: 0.5rem;
}

input::placeholder {
    color: #94a3b8; /* Placeholder gris claro */
    opacity: 0.7;
}

input:focus, select:focus {
    outline: none;
    border-color: #3b82f6; /* Azul claro */
    box-shadow: 0 0 0 2px #3b82f6;
}

</style>
</head>
<body class="font-sans">

    <div class="flex flex-col min-h-screen">
        <header class="bg-gradient-to-r from-green-600 to-blue-600 text-white px-4 py-3 flex justify-between items-center shadow-md">
            <h1 class="text-xl font-bold">🚖 Seguimiento del Viaje</h1>
        </header>

        <main class="flex-1 p-4 space-y-6">
            <div id="map"></div>
            <div class="bg-white p-4 rounded-xl shadow-md space-y-4">
                <h2 class="text-lg font-bold">Detalles del Viaje</h2>
                <p id="status" class="text-sm text-indigo-600 font-bold">
                    <?php echo htmlspecialchars($trip['status'] === 'asignado' ? 'El conductor está en camino.' : 'Esperando a que un conductor tome su solicitud...'); ?>
                </p>
                <p id="pickup" class="text-sm"><strong>Recoger en:</strong> <?php echo htmlspecialchars($trip['pickup_address'] ?? 'No disponible'); ?></p>
                <p id="destination" class="text-sm"><strong>Destino:</strong> <?php echo htmlspecialchars($trip['destination_address'] ?? 'No disponible'); ?></p>
                <p id="distance" class="text-sm"><strong>Distancia:</strong> <?php echo htmlspecialchars($trip['distance'] ?? '0'); ?> km</p>
                <p id="fare" class="text-sm"><strong>Tarifa:</strong> $<?php echo htmlspecialchars($trip['fare'] ?? '0.00'); ?></p>
                <p id="driver" class="text-sm text-green-600"><strong>Conductor:</strong> Esperando asignación...</p>
                <p id="driverPlate" class="text-sm"><strong>Placa:</strong> N/A</p>
            </div>
        </main>

        <div class="notification" id="notification">Notificación</div>
    </div>

    <script>
        let map, driverMarker, previousStatus = null;

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 28.1973, lng: -105.4702 },
                zoom: 14,
            });

            driverMarker = new google.maps.Marker({
                map,
                title: "Conductor",
                icon: "http://maps.google.com/mapfiles/ms/icons/taxi.png",
            });

            updateTripStatus();
        }

        function updateTripStatus() {
    const tripId = <?php echo json_encode($tripId); ?>;

    fetch(`trip_status.php?trip_id=${tripId}`)
        .then((response) => response.json())
        .then((data) => {
            if (data.status === "asignado") {
                document.getElementById("status").textContent = "El conductor está en camino.";
                document.getElementById("driver").textContent = `Conductor: ${data.driver_name}`;
                document.getElementById("driverPlate").textContent = `Placa: ${data.driver_plate}`;
                document.getElementById("passengerPhone").textContent = `Teléfono del pasajero: ${data.passenger_phone}`;

                const driverLocation = data.driver_location;
                if (driverLocation) {
                    driverMarker.setPosition(driverLocation);
                    map.setCenter(driverLocation);
                }
            }

            if (data.status !== previousStatus) {
                if (data.status === "afuera") {
                    showNotification("El conductor está afuera.");
                } else if (data.status === "completado") {
                    showNotification("El viaje ha finalizado.");
                    setTimeout(() => {
                        window.location.href = "dashboardpa.php";
                    }, 3000);
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

        window.onload = initMap;
    </script>
</body>
</html>
