<?php
include 'db.php';
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$tripId = $_GET['trip_id'] ?? null;
$driverId = $_SESSION['user_id'];

if (!$tripId) {
    echo "ID de viaje no especificado.";
    exit;
}

$stmt = $conn->prepare("
    SELECT
        trips.pickup_address AS pickup,
        trips.destination_address AS destination,
        trips.distance,
        trips.fare,
        trips.status,
        users.name AS passenger_name,
        users.phone AS passenger_phone
    FROM trips
    INNER JOIN users ON trips.passenger_id = users.id
    WHERE trips.id = ? AND trips.driver_id = ?
");

$stmt->bind_param("ii", $tripId, $driverId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $trip = $result->fetch_assoc();
} else {
    echo "No tienes acceso a este viaje.";
    exit;
}

$mapsApiKey = GOOGLE_MAPS_API_KEY;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento del Conductor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($mapsApiKey); ?>&libraries=places"></script>
    <style>
        #map { width: 100%; height: 400px; border-radius: 8px; }
        #voice-control {
            position: absolute; bottom: 20px; right: 20px; z-index: 1000;
            background-color: rgba(0, 0, 0, 0.7); color: white; border: none;
            border-radius: 50%; width: 40px; height: 40px; font-size: 20px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-black text-white font-sans">
    <div class="flex flex-col min-h-screen">
        <header class="bg-gradient-to-r from-gray-800 via-gray-900 to-black text-white px-4 py-3 flex justify-between items-center shadow-md">
            <h1 class="text-xl font-bold">Seguimiento del Conductor</h1>
        </header>

        <main class="flex-1 p-4 space-y-6">
            <div id="map" class="rounded-xl shadow-md"></div>
            <button id="voice-control" title="Activar/Desactivar Voz">&#128266;</button>

            <div class="bg-gray-800 p-6 rounded-xl shadow-md space-y-4">
                <h2 class="text-lg font-bold text-blue-500">Detalles del Viaje</h2>
                <p id="pickup" class="text-sm"><strong>Recoger en:</strong> <?php echo htmlspecialchars($trip['pickup']); ?></p>
                <p id="destination" class="text-sm"><strong>Destino:</strong> <?php echo htmlspecialchars($trip['destination']); ?></p>
                <p id="distance" class="text-sm"><strong>Distancia:</strong> <?php echo htmlspecialchars($trip['distance']); ?> km</p>
                <p id="fare" class="text-sm"><strong>Tarifa:</strong> $<?php echo htmlspecialchars($trip['fare']); ?></p>
                <p id="phone" class="text-sm"><strong>Telefono del pasajero:</strong>
                    <a href="tel:<?php echo htmlspecialchars($trip['passenger_phone']); ?>" class="text-blue-500 hover:underline">
                        <?php echo htmlspecialchars($trip['passenger_phone']); ?>
                    </a>
                </p>

                <button id="action-button" class="w-full bg-green-500 text-white py-3 rounded-lg font-bold hover:bg-green-600 transition">
                    Estoy afuera
                </button>
            </div>
        </main>
    </div>

    <script>
        let map, directionsRenderer, directionsService, driverMarker, speechSynthesis, isVoiceActive = true;
        let currentAction = "pickup";

        function initMap() {
            directionsRenderer = new google.maps.DirectionsRenderer({ suppressMarkers: false, preserveViewport: true });
            directionsService = new google.maps.DirectionsService();
            map = new google.maps.Map(document.getElementById("map"), { center: { lat: 28.1973, lng: -105.4702 }, zoom: 14 });
            directionsRenderer.setMap(map);
            driverMarker = new google.maps.Marker({ map, title: "Tu ubicacion", icon: "http://maps.google.com/mapfiles/ms/icons/taxi.png" });

            setupVoice();
            setupVoiceControl();
            trackDriverLocation();
            setupActionButton();
        }

        function setupVoice() {
            if ('speechSynthesis' in window) { speechSynthesis = window.speechSynthesis; }
        }

        function narrate(text) {
            if (speechSynthesis && isVoiceActive) {
                const utterance = new SpeechSynthesisUtterance(text);
                speechSynthesis.speak(utterance);
            }
        }

        function setupVoiceControl() {
            const btn = document.getElementById("voice-control");
            btn.addEventListener("click", () => {
                isVoiceActive = !isVoiceActive;
                btn.textContent = isVoiceActive ? "\u{1F50A}" : "\u{1F507}";
            });
        }

        function trackDriverLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.watchPosition(
                    (position) => {
                        const loc = { lat: position.coords.latitude, lng: position.coords.longitude };
                        driverMarker.setPosition(loc);
                        map.setCenter(loc);
                        const dest = currentAction === "pickup"
                            ? <?php echo json_encode($trip['pickup']); ?>
                            : <?php echo json_encode($trip['destination']); ?>;
                        calculateAndDisplayRoute(loc, dest);
                    },
                    (error) => { console.error("Error al obtener ubicacion:", error); },
                    { enableHighAccuracy: true }
                );
            }
        }

        function calculateAndDisplayRoute(origin, destinationAddress) {
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ address: destinationAddress }, (results, status) => {
                if (status === "OK") {
                    directionsService.route(
                        { origin, destination: results[0].geometry.location, travelMode: google.maps.TravelMode.DRIVING },
                        (result, status) => {
                            if (status === google.maps.DirectionsStatus.OK) {
                                directionsRenderer.setDirections(result);
                                const steps = result.routes[0].legs[0].steps;
                                steps.forEach((step, index) => {
                                    setTimeout(() => {
                                        narrate(step.instructions.replace(/<[^>]*>/g, '') + ` en ${Math.round(step.distance.value)} metros.`);
                                    }, index * 5000);
                                });
                            }
                        }
                    );
                }
            });
        }

        function setupActionButton() {
            const actionButton = document.getElementById("action-button");
            actionButton.addEventListener("click", () => {
                const tripId = <?php echo json_encode($tripId); ?>;
                if (currentAction === "pickup") {
                    currentAction = "destination";
                    actionButton.textContent = "Pedido finalizado";
                    fetch("update_trip_status.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ trip_id: tripId, status: "en_destino" }),
                    });
                } else {
                    fetch("update_trip_status.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ trip_id: tripId, status: "completado" }),
                    }).then(() => { window.location.href = "dashboard.php"; });
                }
            });
        }

        window.onload = initMap;
    </script>
</body>
</html>
