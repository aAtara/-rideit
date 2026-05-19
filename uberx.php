<?php
include 'db.php';
include 'csrf.php';
require_once 'config.php';
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login_pasajero.php");
    exit;
}

// Obtener información del usuario
$userId = $_SESSION['user_id'];

// Obtener ubicaciones guardadas
$stmt = $conn->prepare("SELECT type, address, lat, lng FROM preferences WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$savedLocations = [];
while ($row = $result->fetch_assoc()) {
    $savedLocations[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar un Viaje - TuApp</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars(GOOGLE_MAPS_API_KEY); ?>&libraries=places"></script>
    <style>
    body {
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        color: #f8fafc;
    }
    #map {
        width: 100%;
        height: 300px;
        border-radius: 8px;
    }
    .bg-white {
        background-color: #334155;
        color: #f8fafc;
    }
    .text-gray-700 {
        color: #94a3b8;
    }
    .text-green-600 {
        color: #22c55e;
    }
    .text-indigo-600 {
        color: #6366f1;
    }
    .bg-blue-500 {
        background-color: #3b82f6;
    }
    .hover\:bg-blue-600:hover {
        background-color: #2563eb;
    }
    .text-blue-600 {
        color: #3b82f6;
    }
    .hover\:text-blue-800:hover {
        color: #1e40af;
    }
    .border-gray-200 {
        border-color: #1e293b;
    }
    .shadow-md {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .shadow-inner {
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .rounded-lg {
        border-radius: 8px;
    }
    input, select {
    background-color: #1e293b; /* Fondo negro */
    color: #f8fafc; /* Texto claro */
    border: 1px solid #475569; /* Borde gris */
    border-radius: 8px; /* Bordes redondeados */
    padding: 0.5rem; /* Espaciado interno */
}

input::placeholder {
    color: #94a3b8; /* Color del texto del placeholder */
    opacity: 0.7;
}

select:focus, input:focus {
    outline: none;
    border-color: #3b82f6; /* Color del borde al hacer foco */
    box-shadow: 0 0 0 2px #3b82f6; /* Sombra azul al enfocar */
}

</style>

</head>
<body class="font-sans">

    <!-- Contenedor principal -->
    <div class="flex flex-col min-h-screen">

        <!-- Barra de navegación superior -->
        <header class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-4 py-3 flex justify-between items-center shadow-md">
            <h1 class="text-xl font-bold">🚖 Solicitar un Viaje</h1>
            <a href="dashboardpa.php" class="bg-white text-blue-500 px-4 py-2 rounded-lg text-sm font-bold shadow-md hover:shadow-lg">
                Volver
            </a>
        </header>

        <!-- Contenido principal -->
        <main class="flex-1 p-4 space-y-6">
            <!-- Mapa -->
            <div id="map"></div>

            <!-- Ubicaciones -->
            <div class="bg-white p-4 rounded-xl shadow-md space-y-4">
                <label for="saved-locations" class="block text-sm font-medium text-gray-700">Ubicaciones Guardadas</label>
                <select id="saved-locations" class="w-full px-4 py-2 border rounded-lg mt-2">
                    <option value="">Selecciona una ubicación</option>
                    <?php foreach ($savedLocations as $location): ?>
                        <option value='<?php echo json_encode($location); ?>'>
                            <?php echo htmlspecialchars($location['type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="pickup" class="block text-sm font-medium text-gray-700">Dirección de Recogida</label>
                <input type="text" id="pickup" placeholder="Moviendo el marcador se actualizará esta dirección" class="w-full px-4 py-2 border rounded-lg mt-2" readonly>

                <label for="destination" class="block text-sm font-medium text-gray-700">Dirección de Destino</label>
                <input type="text" id="destination" placeholder="Ingresa una dirección" class="w-full px-4 py-2 border rounded-lg mt-2">
            </div>

            <!-- Detalles del viaje -->
            <div class="bg-white p-4 rounded-xl shadow-md space-y-4">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium text-gray-700">Tarifa Estimada:</p>
                    <p id="estimated-fare" class="text-sm font-bold text-green-600">$0</p>
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium text-gray-700">Distancia:</p>
                    <p id="distance" class="text-sm font-bold text-indigo-600">0 km</p>
                </div>
            </div>

            <!-- Formulario para confirmar viaje -->
            <form action="request_trip.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="pickup" id="pickup-hidden">
                <input type="hidden" name="destination" id="destination-hidden">
                <input type="hidden" name="distance" id="distance-hidden">
                <input type="hidden" name="fare" id="fare-hidden">
                <button type="submit" id="confirm-ride" class="w-full bg-blue-500 text-white py-3 rounded-lg font-bold hover:bg-blue-600 transition">
                    Confirmar Viaje
                </button>
            </form>
        </main>

        <!-- Barra de navegación inferior -->
        <nav class="bg-white shadow-inner fixed bottom-0 left-0 w-full flex justify-around py-2 border-t border-gray-200">
            <a href="dashboardpa.php" class="text-center text-blue-600 hover:text-blue-800">
                <i class="fas fa-home text-2xl"></i>
                <p class="text-xs">Inicio</p>
            </a>
            <a href="profilepa.php" class="text-center text-blue-600 hover:text-blue-800">
                <i class="fas fa-user text-2xl"></i>
                <p class="text-xs">Perfil</p>
            </a>
        </nav>
    </div>

    <!-- Script -->
    <script>
        let map, directionsRenderer, directionsService, pickupMarker, destinationMarker;

        function initMap() {
            directionsRenderer = new google.maps.DirectionsRenderer();
            directionsService = new google.maps.DirectionsService();

            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 28.1973, lng: -105.4702 },
                zoom: 14,
            });

            directionsRenderer.setMap(map);

            pickupMarker = new google.maps.Marker({
                map,
                draggable: true,
                title: "Ubicación de recogida",
                icon: "http://maps.google.com/mapfiles/ms/icons/green-dot.png",
            });

            destinationMarker = new google.maps.Marker({
                map,
                draggable: false,
                title: "Destino",
                icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png",
            });

            loadSavedLocations();

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };

                        map.setCenter(userLocation);
                        pickupMarker.setPosition(userLocation);
                        updatePickupLocation(userLocation);
                    },
                    (error) => {
                        console.error("Error al obtener ubicación:", error);
                        alert("No se pudo obtener tu ubicación. Asegúrate de habilitar el GPS.");
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            } else {
                alert("Tu navegador no soporta geolocalización.");
            }

            pickupMarker.addListener("dragend", () => {
                const position = pickupMarker.getPosition();
                updatePickupLocation({
                    lat: position.lat(),
                    lng: position.lng(),
                });
            });

            const destinationInput = document.getElementById("destination");
            const autocomplete = new google.maps.places.Autocomplete(destinationInput);
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();
                if (place.geometry && place.geometry.location) {
                    destinationMarker.setPosition(place.geometry.location);
                    map.setCenter(place.geometry.location);
                    calculateRoute();
                }
            });

            document.getElementById("saved-locations").addEventListener("change", (e) => {
                const locationData = e.target.value;
                if (locationData) {
                    const location = JSON.parse(locationData);
                    const latLng = { lat: parseFloat(location.lat), lng: parseFloat(location.lng) };
                    map.setCenter(latLng);
                    pickupMarker.setPosition(latLng);
                    updatePickupLocation(latLng);
                }
            });

            document.getElementById("confirm-ride").addEventListener("click", (event) => {
                const pickup = document.getElementById("pickup").value;
                const destination = document.getElementById("destination").value;
                const distance = document.getElementById("distance").textContent.replace(" km", "");
                const fare = document.getElementById("estimated-fare").textContent.replace("$", "");

                if (pickup && destination) {
                    document.getElementById("pickup-hidden").value = pickup;
                    document.getElementById("destination-hidden").value = destination;
                    document.getElementById("distance-hidden").value = parseFloat(distance);
                    document.getElementById("fare-hidden").value = parseFloat(fare);
                } else {
                    event.preventDefault();
                    alert("Por favor selecciona las ubicaciones.");
                }
            });
        }

        function loadSavedLocations() {
            const savedLocations = <?php echo json_encode($savedLocations); ?>;
            const select = document.getElementById("saved-locations");
            savedLocations.forEach((location) => {
                const option = document.createElement("option");
                option.value = JSON.stringify(location);
                option.textContent = location.type;
                select.appendChild(option);
            });
        }

        function updatePickupLocation(location) {
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location }, (results, status) => {
                if (status === "OK" && results[0]) {
                    document.getElementById("pickup").value = results[0].formatted_address;
                    calculateRoute();
                }
            });
        }

        function calculateRoute() {
            const pickup = pickupMarker.getPosition();
            const destination = destinationMarker.getPosition();

            if (pickup && destination) {
                directionsService.route(
                    {
                        origin: pickup,
                        destination: destination,
                        travelMode: google.maps.TravelMode.DRIVING,
                    },
                    (result, status) => {
                        if (status === google.maps.DirectionsStatus.OK) {
                            directionsRenderer.setDirections(result);

                            const distance = result.routes[0].legs[0].distance.value / 1000;
                            const fare = 10 * distance;

                            document.getElementById("distance").textContent = `${distance.toFixed(2)} km`;
                            document.getElementById("estimated-fare").textContent = `$${fare.toFixed(2)}`;
                        }
                    }
                );
            }
        }

        window.onload = initMap;
    </script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
