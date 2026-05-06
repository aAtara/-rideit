<?php
// Incluir la conexión a la base de datos
include 'db.php';
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

// Obtener el ID del viaje
$tripId = $_GET['trip_id'] ?? null;

if (!$tripId) {
    echo "ID de viaje no especificado.";
    exit;
}

// Obtener los detalles del viaje y el pasajero
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
    WHERE trips.id = ?
");

$stmt->bind_param("i", $tripId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $trip = $result->fetch_assoc();
} else {
    echo "No se encontraron detalles del viaje.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento del Conductor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA3xKp9oCPeRfduSHv29G_nph7u4rLHQVI&libraries=places"></script>
    <style>
        #map {
            width: 100%;
            height: 400px;
            border-radius: 8px;
        }
        #voice-control {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-black text-white font-sans">
    <div class="flex flex-col min-h-screen">
        <!-- Barra de navegación superior -->
        <header class="bg-gradient-to-r from-gray-800 via-gray-900 to-black text-white px-4 py-3 flex justify-between items-center shadow-md">
            <h1 class="text-xl font-bold">🚖 Seguimiento del Conductor</h1>
        </header>

        <!-- Contenido principal -->
        <main class="flex-1 p-4 space-y-6">
            <!-- Mapa -->
            <div id="map" class="rounded-xl shadow-md"></div>
            <button id="voice-control" title="Activar/Desactivar Voz">🔊</button>

            <!-- Información del viaje -->
            <div class="bg-gray-800 p-6 rounded-xl shadow-md space-y-4">
                <h2 class="text-lg font-bold text-blue-500">Detalles del Viaje</h2>
                <p id="pickup" class="text-sm"><strong>Recoger en:</strong> <?php echo htmlspecialchars($trip['pickup']); ?></p>
                <p id="destination" class="text-sm"><strong>Destino:</strong> <?php echo htmlspecialchars($trip['destination']); ?></p>
                <p id="distance" class="text-sm"><strong>Distancia:</strong> <?php echo $trip['distance']; ?> km</p>
                <p id="fare" class="text-sm"><strong>Tarifa:</strong> $<?php echo $trip['fare']; ?></p>
                <p id="phone" class="text-sm"><strong>Teléfono del pasajero:</strong> 
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

    <!-- Script -->
    <script>
        let map, directionsRenderer, directionsService, driverMarker, speechSynthesis, isVoiceActive = true;
        let currentAction = "pickup";

        function initMap() {
            directionsRenderer = new google.maps.DirectionsRenderer({
                suppressMarkers: false,
                preserveViewport: true
            });
            directionsService = new google.maps.DirectionsService();

            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 28.1973, lng: -105.4702 },
                zoom: 14,
            });

            directionsRenderer.setMap(map);

            driverMarker = new google.maps.Marker({
                map,
                title: "Tu ubicación",
                icon: "http://maps.google.com/mapfiles/ms/icons/taxi.png",
            });

            setupVoice();
            setupVoiceControl();
            trackDriverLocation();
            setupActionButton();
        }

        function setupVoice() {
            if ('speechSynthesis' in window) {
                speechSynthesis = window.speechSynthesis;
            } else {
                alert("Tu navegador no soporta la narración por voz.");
            }
        }

        function narrate(text) {
            if (speechSynthesis && isVoiceActive) {
                const utterance = new SpeechSynthesisUtterance(text);
                speechSynthesis.speak(utterance);
            }
        }

        function setupVoiceControl() {
            const voiceControlButton = document.getElementById("voice-control");
            voiceControlButton.addEventListener("click", () => {
                isVoiceActive = !isVoiceActive;
                voiceControlButton.textContent = isVoiceActive ? "🔊" : "🔇";
            });
        }

        function trackDriverLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.watchPosition(
                    (position) => {
                        const driverLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };
                        driverMarker.setPosition(driverLocation);
                        map.setCenter(driverLocation);

                        const destination = currentAction === "pickup" 
                            ? "<?php echo htmlspecialchars($trip['pickup']); ?>" 
                            : "<?php echo htmlspecialchars($trip['destination']); ?>";

                        calculateAndDisplayRoute(driverLocation, destination);
                    },
                    (error) => {
                        console.error("Error al obtener ubicación:", error);
                        alert("No se pudo obtener tu ubicación en tiempo real.");
                    },
                    { enableHighAccuracy: true }
                );
            } else {
                alert("Tu navegador no soporta geolocalización.");
            }
        }

        function calculateAndDisplayRoute(origin, destinationAddress) {
            const geocoder = new google.maps.Geocoder();

            geocoder.geocode({ address: destinationAddress }, (results, status) => {
                if (status === "OK") {
                    const destinationLatLng = results[0].geometry.location;

                    directionsService.route(
                        {
                            origin,
                            destination: destinationLatLng,
                            travelMode: google.maps.TravelMode.DRIVING,
                        },
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
                } else {
                    console.error("Error al geocodificar dirección:", status);
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
                    }).then(() => {
                        window.location.href = "dashboard.php";
                    });
                }
            });
        }

        window.onload = initMap;
    </script>
</body>
</html>
