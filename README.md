# RideIt - Plataforma de Transporte

Aplicacion web de transporte tipo ride-sharing desarrollada como proyecto de Ingenieria de Software.

## Descripcion

RideIt conecta pasajeros con conductores para servicios de transporte en la ciudad de Delicias, Chihuahua. Los pasajeros pueden solicitar viajes, ver tarifas estimadas, rastrear al conductor en tiempo real y calificar el servicio.

## Tecnologias

- **Backend:** PHP 8.x con MySQLi
- **Frontend:** HTML5, TailwindCSS, JavaScript
- **Mapas:** Google Maps API (Places, Directions, Geocoding)
- **Base de datos:** MySQL / MariaDB
- **Hosting:** InfinityFree / Compatible con Docker

## Estructura del proyecto

```
Rideit/
├── config.php          # Configuracion y variables de entorno
├── db.php              # Conexion a base de datos
├── csrf.php            # Proteccion CSRF
├── schema.sql          # Esquema de base de datos
├── .env                # Variables de entorno (no incluido en repo)
├── .htaccess           # Reglas de seguridad Apache
├── Dockerfile          # Despliegue con Docker
│
├── login_pasajero.php  # Login de pasajeros
├── login.php           # Login de conductores
├── register_pasajero.php # Registro de pasajeros
├── registrocon.php     # Registro de conductores
├── logoutpa.php        # Cierre de sesion pasajeros
├── logout.php          # Cierre de sesion conductores
│
├── dashboardpa.php     # Panel principal del pasajero
├── dashboard.php       # Panel principal del conductor
├── profilepa.php       # Perfil del pasajero
├── profile.php         # Perfil del conductor
│
├── uberx.php           # Solicitud de viaje con mapa
├── request_trip.php    # Procesamiento de solicitud
├── accept_trip.php     # Aceptar viaje (conductor)
├── update_trip_status.php # Actualizar estado del viaje
├── track_driver.php    # Tracking vista conductor
├── trackingpa.php      # Tracking vista pasajero (con SOS)
├── trip_status.php     # API estado del viaje
│
├── calificar_viaje.php # Calificacion post-viaje
├── historial_pasajero.php # Historial de viajes
├── help.php            # Ayuda, FAQ y soporte
├── privacidad.php      # Aviso de privacidad
├── terminos.php        # Terminos y condiciones
├── sos_alert.php       # Endpoint boton de panico
│
├── formulario.php      # Formulario postulacion conductor
├── postulantes.php     # Admin: ver postulantes
└── uploads/            # Fotos de perfil
    └── .htaccess       # Bloquea ejecucion PHP en uploads
```

## Modulos principales

1. **Autenticacion** - Login/logout con sesiones seguras y remember-me
2. **Registro** - Registro de pasajeros y conductores con validaciones
3. **Perfil** - Edicion de datos personales y direcciones guardadas
4. **Solicitud de viaje** - Seleccion de origen/destino en mapa interactivo
5. **Estimacion de tarifa** - Calculo automatico de tarifa, distancia y ETA
6. **Asignacion de conductor** - Matching conductor-pasajero
7. **Tracking en tiempo real** - Seguimiento GPS del conductor
8. **Boton de panico (SOS)** - Alerta de emergencia con ubicacion
9. **Calificacion** - Sistema de estrellas post-viaje
10. **Historial** - Consulta de viajes anteriores
11. **Ayuda y soporte** - FAQ y reportes de incidentes

## Seguridad

- Contrasenas hasheadas con `password_hash()` (bcrypt)
- Proteccion CSRF en todos los formularios
- Prepared statements (prevencion SQL injection)
- Validacion de entrada (email, telefono, contrasena)
- Sesiones seguras con `session_regenerate_id()`
- Variables sensibles en `.env` (fuera del repositorio)
- `.htaccess` bloquea acceso a archivos sensibles

## Configuracion

1. Copiar `.env.example` a `.env` y configurar las credenciales
2. Importar `schema.sql` en la base de datos MySQL
3. Configurar la API key de Google Maps en `.env`

## Equipo

- Erik Eduardo Esparza Jaquez - 22540285
- Manuel Cayetano Martinez Hernandez - 22540300
- Ivan Eduardo Lujan Sanchez - 23540270
- Moises Diaz - 24540693

## Institucion

Instituto Tecnologico de Delicias - Ingenieria de Software

Docente: Sofia Irene Diaz Ortiz
