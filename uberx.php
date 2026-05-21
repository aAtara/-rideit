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

// Obtener metodo de pago del usuario
$stmtPay = $conn->prepare("SELECT payment_method FROM users WHERE id = ?");
$stmtPay->bind_param("i", $userId);
$stmtPay->execute();
$payResult = $stmtPay->get_result();
$payRow = $payResult->fetch_assoc();
$userPaymentMethod = $payRow['payment_method'] ?? 'efectivo';

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
    <title>Solicitar un Viaje - RideIt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <link rel="stylesheet" href="modal.css">
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

            <!-- Tipo de servicio estilo Uber -->
            <div class="bg-white p-4 rounded-xl shadow-md space-y-4">
                <label class="block text-sm font-medium text-gray-700">Tipo de Servicio</label>
                <div class="space-y-3" id="service-type-selector">
                    <!-- Economico -->
                    <button type="button" onclick="selectServiceType('economico')" id="btn-economico" class="w-full p-4 rounded-xl border-2 border-blue-500 bg-blue-500/10 text-left transition flex items-start gap-3">
                        <div class="text-3xl mt-1">🚗</div>
                        <div class="flex-1">
                            <div class="flex justify-between items-center">
                                <p class="text-lg font-bold">Economico</p>
                                <span class="text-xs bg-green-600/30 text-green-300 px-2 py-0.5 rounded-full font-bold">x1.0</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Viajes rapidos y accesibles en vehiculos estandar. La opcion mas economica para tus trayectos diarios. Hasta 4 pasajeros.</p>
                            <div class="flex gap-2 mt-2">
                                <span class="text-xs bg-gray-700 text-gray-300 px-2 py-0.5 rounded">4 pasajeros</span>
                                <span class="text-xs bg-gray-700 text-gray-300 px-2 py-0.5 rounded">Estandar</span>
                            </div>
                        </div>
                    </button>
                    <!-- Confort -->
                    <button type="button" onclick="selectServiceType('confort')" id="btn-confort" class="w-full p-4 rounded-xl border-2 border-gray-600 text-left transition hover:border-purple-500 flex items-start gap-3">
                        <div class="text-3xl mt-1">✨</div>
                        <div class="flex-1">
                            <div class="flex justify-between items-center">
                                <p class="text-lg font-bold">Confort</p>
                                <span class="text-xs bg-purple-600/30 text-purple-300 px-2 py-0.5 rounded-full font-bold">x1.35</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Vehiculos mas nuevos con mayor espacio y comodidad. Conductores con calificacion alta. Aire acondicionado garantizado. Viaje silencioso disponible.</p>
                            <div class="flex gap-2 mt-2 flex-wrap">
                                <span class="text-xs bg-gray-700 text-gray-300 px-2 py-0.5 rounded">Mayor espacio</span>
                                <span class="text-xs bg-gray-700 text-gray-300 px-2 py-0.5 rounded">A/C</span>
                                <span class="text-xs bg-gray-700 text-gray-300 px-2 py-0.5 rounded">Conductores ★4.85+</span>
                            </div>
                        </div>
                    </button>
                </div>

                <label class="block text-sm font-medium text-gray-700 mt-3">Metodo de Pago</label>
                <select id="payment-method" class="w-full px-4 py-2 border rounded-lg">
                    <option value="efectivo" <?php echo $userPaymentMethod === 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                    <option value="tarjeta_debito" <?php echo $userPaymentMethod === 'tarjeta_debito' ? 'selected' : ''; ?>>Tarjeta de Debito</option>
                    <option value="tarjeta_credito" <?php echo $userPaymentMethod === 'tarjeta_credito' ? 'selected' : ''; ?>>Tarjeta de Credito</option>
                </select>
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
                <div class="flex justify-between items-center">
                    <p class="text-sm font-medium text-gray-700">Tiempo Estimado (ETA):</p>
                    <p id="eta" class="text-sm font-bold text-indigo-600">-- min</p>
                </div>
            </div>

            <!-- Formulario para confirmar viaje -->
            <form action="request_trip.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="pickup" id="pickup-hidden">
                <input type="hidden" name="destination" id="destination-hidden">
                <input type="hidden" name="distance" id="distance-hidden">
                <input type="hidden" name="fare" id="fare-hidden">
                <input type="hidden" name="service_type" id="service-type-hidden" value="economico">
                <input type="hidden" name="payment_method" id="payment-method-hidden" value="<?php echo htmlspecialchars($userPaymentMethod); ?>">
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
                        RideIt.alert({ title: 'GPS no disponible', message: 'No se pudo obtener tu ubicacion. Asegurate de habilitar el GPS en tu dispositivo.', type: 'warning' });
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            } else {
                RideIt.alert({ title: 'Navegador incompatible', message: 'Tu navegador no soporta geolocalizacion. Utiliza un navegador moderno.', type: 'danger' });
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
                event.preventDefault();
                const pickup = document.getElementById("pickup").value;
                const destination = document.getElementById("destination").value;
                const distance = document.getElementById("distance").textContent.replace(" km", "");
                const fare = document.getElementById("estimated-fare").textContent.replace("$", "");
                const serviceType = document.getElementById("service-type-hidden").value;
                const paymentMethod = document.getElementById("payment-method").value;
                const serviceLabels = { economico: 'Economico', confort: 'Confort' };
                const payLabels = { efectivo: 'Efectivo', tarjeta_debito: 'Tarjeta de Debito', tarjeta_credito: 'Tarjeta de Credito' };

                if (!pickup || !destination) {
                    RideIt.alert({ title: 'Ubicaciones requeridas', message: 'Debes seleccionar un punto de recogida y un destino antes de confirmar el viaje.', type: 'warning' });
                    return;
                }
                if (parseFloat(distance) <= 0) {
                    RideIt.alert({ title: 'Ruta no calculada', message: 'Espera a que se calcule la ruta antes de confirmar.', type: 'warning' });
                    return;
                }

                RideIt.confirm({
                    title: 'Confirmar viaje',
                    message: '<strong>Servicio:</strong> ' + (serviceLabels[serviceType] || 'Economico') + '<br><strong>Destino:</strong> ' + destination + '<br><strong>Distancia:</strong> ' + distance + ' km<br><strong>Tarifa:</strong> $' + parseFloat(fare).toFixed(2) + '<br><strong>Pago:</strong> ' + (payLabels[paymentMethod] || 'Efectivo') + '<br><br>¿Deseas solicitar este viaje?',
                    type: 'info',
                    confirmText: 'Solicitar viaje',
                    confirmClass: 'btn-success',
                    onConfirm: () => {
                        document.getElementById("pickup-hidden").value = pickup;
                        document.getElementById("destination-hidden").value = destination;
                        document.getElementById("distance-hidden").value = parseFloat(distance);
                        document.getElementById("fare-hidden").value = parseFloat(fare);
                        document.getElementById("payment-method-hidden").value = paymentMethod;
                        document.querySelector('form[action="request_trip.php"]').submit();
                    }
                });
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

        // RF14: Zona de cobertura - Delicias, Chihuahua y alrededores (radio ~30km)
        const COVERAGE_CENTER = { lat: 28.1973, lng: -105.4702 };
        const COVERAGE_RADIUS_KM = 30;

        function isInsideCoverage(lat, lng) {
            const R = 6371; // Radio de la Tierra en km
            const dLat = (lat - COVERAGE_CENTER.lat) * Math.PI / 180;
            const dLng = (lng - COVERAGE_CENTER.lng) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(COVERAGE_CENTER.lat * Math.PI / 180) * Math.cos(lat * Math.PI / 180) *
                      Math.sin(dLng/2) * Math.sin(dLng/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return (R * c) <= COVERAGE_RADIUS_KM;
        }

        function calculateRoute() {
            const pickup = pickupMarker.getPosition();
            const destination = destinationMarker.getPosition();

            if (pickup && destination) {
                // Validar zona de cobertura
                if (!isInsideCoverage(pickup.lat(), pickup.lng())) {
                    RideIt.alert({ title: 'Fuera de cobertura', message: 'La ubicacion de recogida esta fuera de la zona de cobertura de RideIt (Delicias y alrededores, radio de 30 km).', type: 'warning' });
                    document.getElementById("confirm-ride").disabled = true;
                    document.getElementById("confirm-ride").classList.add("opacity-50", "cursor-not-allowed");
                    return;
                }
                if (!isInsideCoverage(destination.lat(), destination.lng())) {
                    RideIt.alert({ title: 'Fuera de cobertura', message: 'El destino esta fuera de la zona de cobertura de RideIt (Delicias y alrededores, radio de 30 km).', type: 'warning' });
                    document.getElementById("confirm-ride").disabled = true;
                    document.getElementById("confirm-ride").classList.add("opacity-50", "cursor-not-allowed");
                    return;
                }
                document.getElementById("confirm-ride").disabled = false;
                document.getElementById("confirm-ride").classList.remove("opacity-50", "cursor-not-allowed");

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
                            const duration = result.routes[0].legs[0].duration.text;
                            const tarifaPorKm = <?php echo json_encode(TARIFA_POR_KM); ?>;
                            const tarifaBase = <?php echo json_encode(TARIFA_BASE); ?>;
                            const multiplier = SERVICE_MULTIPLIER[selectedServiceType] || 1.0;
                            const fare = (tarifaBase + (tarifaPorKm * distance)) * multiplier;

                            document.getElementById("distance").textContent = `${distance.toFixed(2)} km`;
                            document.getElementById("estimated-fare").textContent = `$${fare.toFixed(2)}`;
                            document.getElementById("eta").textContent = duration;
                        }
                    }
                );
            }
        }

        let selectedServiceType = 'economico';
        const SERVICE_MULTIPLIER = { economico: 1.0, confort: 1.35 };

        function selectServiceType(type) {
            selectedServiceType = type;
            document.getElementById('service-type-hidden').value = type;

            // UI toggle - new card layout
            const ecoBtn = document.getElementById('btn-economico');
            const comBtn = document.getElementById('btn-confort');

            ecoBtn.className = 'w-full p-4 rounded-xl border-2 text-left transition flex items-start gap-3 ' +
                (type === 'economico' ? 'border-blue-500 bg-blue-500/10' : 'border-gray-600 hover:border-blue-500');
            comBtn.className = 'w-full p-4 rounded-xl border-2 text-left transition flex items-start gap-3 ' +
                (type === 'confort' ? 'border-purple-500 bg-purple-500/10' : 'border-gray-600 hover:border-purple-500');

            // Recalcular tarifa si ya hay ruta
            calculateRoute();
        }

        // Sync payment method selector with hidden field
        document.addEventListener('DOMContentLoaded', function() {
            const paySelect = document.getElementById('payment-method');
            if (paySelect) {
                paySelect.addEventListener('change', function() {
                    document.getElementById('payment-method-hidden').value = this.value;
                });
            }
        });

        window.onload = initMap;
    </script>
    <script src="modal.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
