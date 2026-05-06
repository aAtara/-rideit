<?php
include 'db.php';
session_start();

define('REMEMBER_COOKIE_NAME', 'rideit_remember');
define('REMEMBER_COOKIE_LIFETIME', 60 * 60 * 24 * 30); // 30 días

function setRememberMe($user_id, $conn) {
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
    $stmt->bind_param("si", $token_hash, $user_id);
    $stmt->execute();
    setcookie(REMEMBER_COOKIE_NAME, $token, time() + REMEMBER_COOKIE_LIFETIME, "/", "", false, true);
}

function checkRememberMe($conn) {
    if (isset($_SESSION['user_id'])) return true;
    if (!isset($_COOKIE[REMEMBER_COOKIE_NAME])) return false;
    $token = $_COOKIE[REMEMBER_COOKIE_NAME];
    $token_hash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $user = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        return true;
    }
    return false;
}

// Intenta restaurar sesión con cookie si no hay sesión activa
if (!isset($_SESSION['user_id'])) {
    checkRememberMe($conn);
}

// Si ya hay sesión, muestra bienvenida con recarga forzada
if (isset($_SESSION['user_id'])) {
    include 'bienvenida.php';
    exit;
}

// --- PROCESO DE LOGIN NORMAL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ? AND role = 'pasajero'");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    setRememberMe($user['id'], $conn);
                    include 'bienvenida.php'; // <-- Aquí va la recarga forzada
                    exit;
                } else {
                    $error = "Contraseña incorrecta. Inténtalo de nuevo.";
                }
            } else {
                $error = "Correo no registrado o el rol no es 'pasajero'. Por favor, verifica.";
            }
        } else {
            $error = "Error en la consulta: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - RideIt Usuario</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #01579B 0%, #003366 100%);
        }
        .glass {
            background: rgba(23, 37, 84, 0.72);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.20);
            backdrop-filter: blur(5px);
            border-radius: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.10);
        }
        .input:focus {
            outline: 2px solid #29B6F6;
            border-color: #29B6F6;
        }
        .login-icon {
            background: linear-gradient(135deg, #29B6F6, #1565C0 65%);
            color: white;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-center items-center">

    <div class="flex flex-col min-h-screen justify-center items-center w-full px-4">

        <div class="glass w-full max-w-sm mx-auto p-8 flex flex-col items-center shadow-2xl animate-fade-in">
            <div class="login-icon mb-4 w-16 h-16 rounded-full flex items-center justify-center shadow-lg animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a8 8 0 00-8 8h16a8 8 0 00-8-8z" />
                </svg>
            </div>
            <h1 class="text-3xl font-extrabold text-white mb-2 text-center tracking-tight drop-shadow-lg">
                Iniciar Sesión
            </h1>
            <p class="text-blue-200 mb-6 text-center">¡Bienvenido de nuevo a <span class="font-bold text-blue-400">RideIt</span>!</p>

            <?php if (isset($error)): ?>
                <div class="bg-red-500/90 text-center p-3 rounded-lg mb-4 text-white shadow-md w-full animate-fade-in-down">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login_pasajero.php" method="POST" class="w-full flex flex-col gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-200 mb-1">Correo electrónico</label>
                    <input type="email" name="email" id="email" placeholder="correo@ejemplo.com" required
                        class="input w-full px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white focus:ring-2 focus:ring-blue-400 transition" />
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-200 mb-1">Contraseña</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" required
                        class="input w-full px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white focus:ring-2 focus:ring-blue-400 transition" />
                </div>
                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-lg mt-2">
                    Iniciar Sesión
                </button>
            </form>

            <div class="mt-6 w-full">
                <a href="register_pasajero.php"
                   class="block w-full bg-gradient-to-r from-green-500 to-green-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-center animate-bounce">
                    ¿No tienes cuenta? Regístrate
                </a>
                <a href="recuperar_password.php" class="block mt-2 text-blue-300 hover:text-blue-400 text-center text-sm transition">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
        </div>
    </div>

    <footer class="bg-black text-gray-300 text-center py-6 mt-8 w-full">
        <p class="text-sm">© 2025 RideIt. Todos los derechos reservados.</p>
    </footer>
    <script>
        // Fade in animation
        document.querySelectorAll('.animate-fade-in').forEach(function(el, i) {
            el.style.opacity = 0;
            setTimeout(() => {
                el.style.transition = "opacity 0.9s";
                el.style.opacity = 1;
            }, 100 + i * 150);
        });
        document.querySelectorAll('.animate-fade-in-down').forEach(function(el, i) {
            el.style.opacity = 0;
            el.style.transform = "translateY(-10px)";
            setTimeout(() => {
                el.style.transition = "opacity 0.9s, transform 0.7s";
                el.style.opacity = 1;
                el.style.transform = "translateY(0)";
            }, 100 + i * 200);
        });
    </script>
</body>
</html>