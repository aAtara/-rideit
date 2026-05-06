<?php
include 'db.php';

// Verificar si se proporcionó el ID del viaje
$tripId = $_GET['trip_id'] ?? null;

if ($tripId) {
    // Consulta para obtener los detalles del viaje
    $stmt = $conn->prepare("
        SELECT 
            t.status, 
            t.pickup_address AS pickup, 
            t.destination_address AS destination, 
            t.distance, 
            t.fare, 
            u.name AS passenger_name, 
            u.phone AS passenger_phone, -- Se incluye el teléfono del pasajero
            d.name AS driver_name, 
            d.lat AS driver_lat, 
            d.lng AS driver_lng, 
            d.plate AS driver_plate
        FROM trips t
        LEFT JOIN users u ON t.passenger_id = u.id
        LEFT JOIN users d ON t.driver_id = d.id
        WHERE t.id = ?
    ");
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $result = $stmt->get_result();
    $trip = $result->fetch_assoc();

    // Si se encuentran los detalles del viaje
    if ($trip) {
        $response = [
            "status" => $trip["status"],
            "pickup" => $trip["pickup"],
            "destination" => $trip["destination"],
            "distance" => $trip["distance"],
            "fare" => $trip["fare"],
            "passenger_name" => $trip["passenger_name"] ?? "Sin información",
            "passenger_phone" => $trip["passenger_phone"] ?? "No disponible", // Campo agregado
            "driver_name" => $trip["driver_name"] ?? "No asignado",
            "driver_plate" => $trip["driver_plate"] ?? "No disponible",
            "driver_location" => ($trip["driver_lat"] && $trip["driver_lng"]) ? [
                "lat" => floatval($trip["driver_lat"]),
                "lng" => floatval($trip["driver_lng"]),
            ] : null
        ];

        // Configurar la respuesta
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // Si no se encuentran detalles del viaje
        http_response_code(404);
        echo json_encode(["error" => "Viaje no encontrado"]);
    }
} else {
    // Si no se proporcionó un ID de viaje
    http_response_code(400);
    echo json_encode(["error" => "ID de viaje no especificado"]);
}
?>
