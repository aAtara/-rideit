<?php
include 'db.php';
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$tripId = $data['trip_id'] ?? null;
$status = $data['status'] ?? null;

if ($tripId && $status) {
    $stmt = $conn->prepare("UPDATE trips SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $tripId);
    $stmt->execute();
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => "Datos inválidos"]);
}
?>
