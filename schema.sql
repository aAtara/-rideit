-- =============================================
-- Schema para RideIt - InfinityFree
-- =============================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('pasajero', 'conductor', 'admin') NOT NULL DEFAULT 'pasajero',
    plate VARCHAR(20) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    remember_token VARCHAR(64) DEFAULT NULL,
    payment_method VARCHAR(30) DEFAULT 'efectivo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar rol admin si la tabla ya existe con ENUM sin admin
-- ALTER TABLE users MODIFY COLUMN role ENUM('pasajero', 'conductor', 'admin') NOT NULL DEFAULT 'pasajero';

CREATE TABLE IF NOT EXISTS trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    driver_id INT DEFAULT NULL,
    pickup_address VARCHAR(255) NOT NULL,
    destination_address VARCHAR(255) NOT NULL,
    distance DOUBLE DEFAULT 0,
    fare DOUBLE DEFAULT 0,
    service_type VARCHAR(20) DEFAULT 'economico',
    status ENUM('pendiente', 'asignado', 'en_destino', 'afuera', 'completado', 'rechazado') NOT NULL DEFAULT 'pendiente',
    payment_method VARCHAR(30) DEFAULT 'efectivo',
    payment_status VARCHAR(20) DEFAULT 'pendiente',
    rating TINYINT DEFAULT NULL,
    comment TEXT DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (passenger_id) REFERENCES users(id),
    FOREIGN KEY (driver_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    address VARCHAR(255) NOT NULL,
    lat DOUBLE NOT NULL,
    lng DOUBLE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL,
    own_car VARCHAR(5) NOT NULL,
    car_details VARCHAR(100) NOT NULL,
    car_year VARCHAR(10) NOT NULL,
    license VARCHAR(5) NOT NULL,
    trustworthy TEXT NOT NULL,
    reliability TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'Pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
