<?php
include 'db.php';
include 'csrf.php';
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = "Token de seguridad invalido. Recarga la pagina.";
    } else {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } elseif (!preg_match('/^[0-9]{7,15}$/', $phone)) {
        $error = "Numero de telefono invalido.";
    } elseif (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "La contrasena debe tener al menos 8 caracteres, incluir letras y numeros.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electronico no es valido.";
    } else {
        // Verificar si el correo ya está registrado
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Este correo ya está registrado.";
        } else {
            // Registrar nuevo pasajero
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'pasajero')");
            $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);

            if ($stmt->execute()) {
                header("Location: login_pasajero.php?success=registered");
                exit;
            } else {
                $error = "Hubo un error en el registro. Intenta de nuevo.";
            }
        }
    }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - RideIt Pasajero</title>
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
        .register-icon {
            background: linear-gradient(135deg, #29B6F6, #1565C0 65%);
            color: white;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-center items-center">

    <div class="flex flex-col min-h-screen justify-center items-center w-full px-4">

        <div class="glass w-full max-w-sm mx-auto p-8 flex flex-col items-center shadow-2xl animate-fade-in">
            <div class="register-icon mb-4 w-16 h-16 rounded-full flex items-center justify-center shadow-lg animate-bounce text-3xl">
                🚖
            </div>
            <h1 class="text-3xl font-extrabold text-white mb-2 text-center tracking-tight drop-shadow-lg">
                Registro de Pasajero
            </h1>
            <p class="text-blue-200 mb-6 text-center">Crea tu cuenta para <span class="font-bold text-blue-400">RideIt</span> y viaja fácil</p>

            <?php if (!empty($error)): ?>
                <div class="bg-red-500/90 text-center p-3 rounded-lg mb-4 text-white shadow-md w-full animate-fade-in-down">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="register_pasajero.php" method="POST" class="w-full flex flex-col gap-4">
                <?php echo csrfField(); ?>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-200 mb-1">Nombre Completo</label>
                    <input type="text" name="name" id="name" placeholder="Tu nombre completo" required
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                        class="input w-full px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white focus:ring-2 focus:ring-blue-400 transition" />
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-200 mb-1">Correo electrónico</label>
                    <input type="email" name="email" id="email" placeholder="correo@ejemplo.com" required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        class="input w-full px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white focus:ring-2 focus:ring-blue-400 transition" />
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-200 mb-1">Teléfono</label>
                    <input type="text" name="phone" id="phone" placeholder="Ej. 5551234567" required
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                        class="input w-full px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white focus:ring-2 focus:ring-blue-400 transition" />
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-200 mb-1">Contraseña</label>
                    <input type="password" name="password" id="password" placeholder="Crea una contraseña segura" required
                        value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>"
                        class="input w-full px-4 py-3 border border-gray-600 rounded-xl bg-gray-900 text-white focus:ring-2 focus:ring-blue-400 transition" />
                    <div id="password-strength" class="mt-2 text-xs hidden">
                        <div class="w-full bg-gray-700 rounded-full h-2 mb-1">
                            <div id="strength-bar" class="h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p id="strength-text" class="text-gray-400"></p>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-2 text-center">
                    Al registrarte, aceptas nuestros
                    <a href="terminos.php" class="text-blue-300 hover:underline">Terminos y Condiciones</a> y
                    <a href="privacidad.php" class="text-blue-300 hover:underline">Aviso de Privacidad</a>.
                </p>
                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-lg mt-2">
                    Registrarse
                </button>
            </form>

            <div class="mt-6 w-full">
                <a href="login_pasajero.php"
                   class="block w-full bg-gradient-to-r from-green-500 to-green-400 text-white py-3 rounded-xl font-bold shadow-xl hover:scale-105 transition text-center animate-bounce">
                    ¿Ya tienes cuenta? Inicia Sesión
                </a>
            </div>
        </div>
    </div>

    <footer class="bg-black text-gray-300 text-center py-6 mt-8 w-full">
        <p class="text-sm">© 2025 RideIt. Todos los derechos reservados.</p>
    </footer>
    <script>
        // Validacion de contraseña en tiempo real
        const passInput = document.getElementById('password');
        const strengthDiv = document.getElementById('password-strength');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');

        passInput.addEventListener('input', function() {
            const val = this.value;
            if (val.length === 0) { strengthDiv.classList.add('hidden'); return; }
            strengthDiv.classList.remove('hidden');

            let score = 0;
            let msgs = [];
            if (val.length >= 8) { score++; } else { msgs.push('Minimo 8 caracteres'); }
            if (/[A-Za-z]/.test(val)) { score++; } else { msgs.push('Incluir letras'); }
            if (/[0-9]/.test(val)) { score++; } else { msgs.push('Incluir numeros'); }

            const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
            const labels = ['Muy debil', 'Debil', 'Aceptable', 'Segura'];
            const pct = [25, 50, 75, 100];

            strengthBar.style.width = pct[score] + '%';
            strengthBar.style.backgroundColor = colors[score];
            strengthText.textContent = labels[score] + (msgs.length ? ' - ' + msgs.join(', ') : '');
            strengthText.style.color = colors[score];
        });

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