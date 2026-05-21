<?php
/**
 * Script para configurar el usuario administrador.
 * Ejecutar UNA sola vez. Luego eliminar o proteger este archivo.
 *
 * USO: Visita setup_admin.php en el navegador
 */
include 'db.php';

// 1. Agregar rol admin al ENUM si no existe
$conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('pasajero', 'conductor', 'admin') NOT NULL DEFAULT 'pasajero'");

// 2. Verificar si ya hay un admin
$result = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if ($result && $result->num_rows > 0) {
    echo "<h2 style='color:green;font-family:sans-serif;'>Ya existe un usuario administrador.</h2>";
    echo "<p style='font-family:sans-serif;'>Si necesitas otro, cambia el email abajo.</p>";
} else {
    // 3. Crear usuario admin
    $adminName = "Administrador RideIt";
    $adminEmail = "admin@rideit.com";
    $adminPhone = "6251234567";
    $adminPassword = password_hash("Admin123", PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'admin')");
    $stmt->bind_param("ssss", $adminName, $adminEmail, $adminPhone, $adminPassword);

    if ($stmt->execute()) {
        echo "<div style='font-family:sans-serif;max-width:500px;margin:50px auto;padding:30px;background:#1e293b;border-radius:12px;color:#e2e8f0;'>";
        echo "<h2 style='color:#22c55e;'>Usuario administrador creado exitosamente</h2>";
        echo "<p><strong>Email:</strong> admin@rideit.com</p>";
        echo "<p><strong>Contrasena:</strong> Admin123</p>";
        echo "<p style='color:#ef4444;margin-top:15px;'><strong>IMPORTANTE:</strong> Cambia la contrasena despues de iniciar sesion y elimina este archivo.</p>";
        echo "<a href='login_admin.php' style='display:inline-block;margin-top:15px;background:#6366f1;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:bold;'>Ir al Login Admin</a>";
        echo "</div>";
    } else {
        echo "<p style='color:red;font-family:sans-serif;'>Error al crear admin: " . $conn->error . "</p>";
    }
}

// 4. Agregar columna comment a trips si no existe
$colCheck = $conn->query("SHOW COLUMNS FROM trips LIKE 'comment'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE trips ADD COLUMN comment TEXT DEFAULT NULL AFTER rating");
    echo "<p style='font-family:sans-serif;color:#22c55e;text-align:center;'>Columna 'comment' agregada a trips.</p>";
} else {
    echo "<p style='font-family:sans-serif;color:#94a3b8;text-align:center;'>Columna 'comment' ya existe.</p>";
}

// 5. Crear tabla sos_alerts si no existe
$conn->query("CREATE TABLE IF NOT EXISTS sos_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    user_id INT NOT NULL,
    lat DOUBLE,
    lng DOUBLE,
    status VARCHAR(20) DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

echo "<p style='font-family:sans-serif;color:#94a3b8;margin-top:10px;text-align:center;'>Tabla sos_alerts verificada.</p>";

// 6. Agregar columna payment_method a users si no existe
$colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'payment_method'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN payment_method VARCHAR(30) DEFAULT 'efectivo'");
    echo "<p style='font-family:sans-serif;color:#22c55e;text-align:center;'>Columna 'payment_method' agregada a users.</p>";
} else {
    echo "<p style='font-family:sans-serif;color:#94a3b8;text-align:center;'>Columna 'payment_method' en users ya existe.</p>";
}

// 7. Agregar columna service_type a trips si no existe
$colCheck = $conn->query("SHOW COLUMNS FROM trips LIKE 'service_type'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE trips ADD COLUMN service_type VARCHAR(20) DEFAULT 'economico' AFTER fare");
    echo "<p style='font-family:sans-serif;color:#22c55e;text-align:center;'>Columna 'service_type' agregada a trips.</p>";
} else {
    echo "<p style='font-family:sans-serif;color:#94a3b8;text-align:center;'>Columna 'service_type' en trips ya existe.</p>";
}

// 8. Agregar columna payment_method a trips si no existe
$colCheck = $conn->query("SHOW COLUMNS FROM trips LIKE 'payment_method'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE trips ADD COLUMN payment_method VARCHAR(30) DEFAULT 'efectivo' AFTER service_type");
    echo "<p style='font-family:sans-serif;color:#22c55e;text-align:center;'>Columna 'payment_method' agregada a trips.</p>";
} else {
    echo "<p style='font-family:sans-serif;color:#94a3b8;text-align:center;'>Columna 'payment_method' en trips ya existe.</p>";
}

// 9. Agregar columna payment_status a trips si no existe
$colCheck = $conn->query("SHOW COLUMNS FROM trips LIKE 'payment_status'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE trips ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pendiente' AFTER payment_method");
    echo "<p style='font-family:sans-serif;color:#22c55e;text-align:center;'>Columna 'payment_status' agregada a trips.</p>";
} else {
    echo "<p style='font-family:sans-serif;color:#94a3b8;text-align:center;'>Columna 'payment_status' en trips ya existe.</p>";
}

// 10. Marcar viajes completados antiguos como pagados
$conn->query("UPDATE trips SET payment_status = 'pagado' WHERE status = 'completado' AND payment_status = 'pendiente'");
echo "<p style='font-family:sans-serif;color:#94a3b8;text-align:center;'>Viajes antiguos marcados como pagados.</p>";
?>
