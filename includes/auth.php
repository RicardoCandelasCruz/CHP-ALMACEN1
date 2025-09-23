<?php
class Auth {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->iniciarSesionSegura();
    }

    private function iniciarSesionSegura() {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionConfig = [
                'cookie_lifetime' => defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 1800,
                'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'cookie_httponly' => true,
                'use_strict_mode' => true,
                'use_only_cookies' => 1,
                'cookie_samesite' => 'Strict'
            ];
            
            session_start($sessionConfig);
        }
        
        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutos
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    
        
        
        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 900) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    public function hacerLogin(string $username, string $password): bool {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, password, nombre, es_admin 
                FROM usuarios 
                WHERE username = :username
                LIMIT 1
            ");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($password, $usuario['password'])) {
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['username'] = $usuario['username'];
                $_SESSION['nombre'] = $usuario['nombre'] ?? '';
                $_SESSION['es_admin'] = (bool)($usuario['es_admin'] ?? false);
                $_SESSION['logged_in'] = true;
                $_SESSION['last_activity'] = time();
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error en autenticación: " . $e->getMessage());
            throw new Exception("Error en el sistema de autenticación");
        }
    }

    public function verificarSesion(): bool {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Verificar tiempo de inactividad
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            $this->cerrarSesion();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }

    public function esAdmin(): bool {
        return $this->verificarSesion() && ($_SESSION['es_admin'] ?? false);
    }
    
    public function obtenerUsuarioId(): int {
        if ($this->verificarSesion() && isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }
        throw new Exception('Usuario no autenticado o ID no disponible');
    }

        
              // En la clase Auth
            public function obtenerUsuarioname(): string {
                 if ($this->verificarSesion() && isset($_SESSION['nombre'])) {
                    return $_SESSION['nombre']; // Removemos el (int) para permitir el string
                 }
                       throw new Exception('Usuario no autenticado o nombre no disponible');
            }   
    

    public function redirigirSegunRol() {
        if (!$this->verificarSesion()) {
            $this->cerrarSesion();
            header("Location: login.php");
            exit();
        }

        $paginaActual = basename($_SERVER['PHP_SELF']);
        
        if ($this->esAdmin() && $paginaActual !== 'index.php') {
            header("Location: index.php");
            exit();
        } elseif (!$this->esAdmin() && $paginaActual !== 'formulario_pedido.php') {
            header("Location: formulario_pedido.php");
            exit();
        }
    }

    public function cerrarSesion() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
}
?>