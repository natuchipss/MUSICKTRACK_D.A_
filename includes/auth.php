<?php
// Sesión, usuario actual y protección CSRF.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_name('musictrack');
    session_start();
}

// ===== Usuario =====

function usuario_actual(): ?array {
    return $_SESSION['usuario'] ?? null;
}

function usuario_id(): int {
    return (int) ($_SESSION['usuario']['id'] ?? 0);
}

function iniciar_sesion(array $usuario): void {
    session_regenerate_id(true); // evita fijación de sesión
    $_SESSION['usuario'] = ['id' => (int) $usuario['id'], 'nombre' => $usuario['nombre']];
}

function cerrar_sesion(): void {
    $_SESSION = [];
    session_regenerate_id(true);
}

// Páginas (index.php): sin sesión, se va al login.
// También comprueba que el usuario siga existiendo (por si se borró o la sesión es de otra base).
function requerir_login(): void {
    if (usuario_actual()) {
        $stmt = db()->prepare('SELECT nombre FROM usuarios WHERE id = :id');
        $stmt->execute([':id' => usuario_id()]);
        $nombre = $stmt->fetchColumn();

        if ($nombre !== false) {
            $_SESSION['usuario']['nombre'] = $nombre;
            return;
        }
        cerrar_sesion();
    }
    header('Location: login.php');
    exit;
}

// ===== CSRF: cada formulario POST lleva un token que solo conoce la sesión =====

function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_campo(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_valido(?string $token): bool {
    return is_string($token) && hash_equals(csrf_token(), $token);
}

// ===== Protección de los archivos de /acciones (formularios) =====
// Exige POST, token CSRF y (salvo login/registro) un usuario con sesión.
function accion_protegida(bool $requiereUsuario = true): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php');
        exit;
    }
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('La sesión expiró o la solicitud no es válida. <a href="../index.php">Volver</a>');
    }
    if ($requiereUsuario && !usuario_actual()) {
        header('Location: ../login.php');
        exit;
    }
}

// ===== Protección de los archivos de /api (JSON) =====
// El token CSRF llega en la cabecera X-CSRF-Token en las peticiones POST.
function api_protegida(): void {
    header('Content-Type: application/json; charset=utf-8');

    if (!usuario_actual()) {
        http_response_code(401);
        exit(json_encode(['error' => 'Inicia sesión']));
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valido($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        http_response_code(403);
        exit(json_encode(['error' => 'Solicitud no válida, recarga la página']));
    }
}
