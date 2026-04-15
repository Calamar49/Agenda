<?php
/**
 * ============================================================================
 * CONEXIÓN REMOTA A BASE DE DATOS - MariaDB vía mysqli
 * ============================================================================
 *
 * ARQUITECTURA DE RED (Laboratorio Fedora Server 43):
 * ┌──────────────────────────┐          ┌──────────────────────────┐
 * │  SERVIDOR WEB (Nodo 1)  │          │  SERVIDOR DB (Nodo 2)   │
 * │  IP: 192.168.56.104     │  ──────► │  IP: 192.168.56.105     │
 * │  Apache + PHP 8.4.x     │  TCP/3306│  MariaDB (puerto 3306)  │
 * │  HTTPS habilitado       │          │  Base de datos: uacdb   │
 * └──────────────────────────┘          └──────────────────────────┘
 *
 * FLUJO DE CONEXIÓN:
 * 1. El cliente accede al servidor web (192.168.56.104) por HTTPS.
 * 2. PHP en el servidor web establece una conexión TCP al servidor
 *    de base de datos (192.168.56.105) en el puerto 3306.
 * 3. SELinux en Fedora debe permitir conexiones salientes de httpd
 *    hacia la red, habilitando: httpd_can_network_connect_db
 *
 * REQUISITOS DE CONFIGURACIÓN EN FEDORA:
 * - En el Nodo 1 (Servidor Web):
 *   $ sudo setsebool -P httpd_can_network_connect_db on
 *   $ sudo firewall-cmd --permanent --add-service=https
 *   $ sudo firewall-cmd --reload
 *
 * - En el Nodo 2 (Servidor DB):
 *   $ sudo firewall-cmd --permanent --add-port=3306/tcp
 *   $ sudo firewall-cmd --reload
 *   MariaDB debe tener el usuario 'manuel' con acceso desde 192.168.56.104:
 *   GRANT ALL PRIVILEGES ON uacdb.* TO 'manuel'@'192.168.56.104' IDENTIFIED BY 'calamar123';
 *   FLUSH PRIVILEGES;
 *
 * @package    AgendaWeb
 * @author     Laboratorio Redes - Fedora Server 43
 * @version    1.0.0
 * @compatible PHP 8.4.x
 */

declare(strict_types=1);

/**
 * Clase Database (Singleton)
 *
 * Gestiona la conexión remota a MariaDB usando la extensión mysqli.
 * Implementa el patrón Singleton para reutilizar una única instancia
 * de conexión durante toda la petición HTTP.
 */
class Database
{
    // ── Parámetros de conexión al Nodo 2 (Servidor de Base de Datos) ──
    private const DB_HOST    = '192.168.56.105';  // IP del servidor MariaDB
    private const DB_PORT    = 3306;               // Puerto estándar de MariaDB
    private const DB_USER    = 'manuel';           // Usuario de la base de datos
    private const DB_PASS    = 'calamar123';       // Contraseña del usuario
    private const DB_NAME    = 'uacdb';            // Nombre de la base de datos
    private const DB_CHARSET = 'utf8mb4';          // Juego de caracteres

    /** @var Database|null Instancia única (Singleton) */
    private static ?Database $instance = null;

    /** @var mysqli Objeto de conexión mysqli */
    private mysqli $connection;

    /**
     * Constructor privado: establece la conexión remota a MariaDB.
     *
     * La conexión se realiza desde el Nodo 1 (192.168.56.104)
     * hacia el Nodo 2 (192.168.56.105) a través de la red interna.
     *
     * @throws RuntimeException Si la conexión falla.
     */
    private function __construct()
    {
        // Configurar el reporte de errores de mysqli como excepciones
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->connection = new mysqli(
                hostname: self::DB_HOST,
                username: self::DB_USER,
                password: self::DB_PASS,
                database: self::DB_NAME,
                port:     self::DB_PORT
            );

            // Establecer el charset para evitar problemas de codificación
            $this->connection->set_charset(self::DB_CHARSET);

        } catch (\mysqli_sql_exception $e) {
            // Registrar el error sin exponer detalles sensibles al cliente
            error_log("[AgendaWeb] Error de conexión a DB ({$e->getCode()}): {$e->getMessage()}");
            throw new RuntimeException(
                'No se pudo establecer la conexión con el servidor de base de datos. '
                . 'Verifique que el servicio MariaDB esté activo en el Nodo 2 (192.168.56.105) '
                . 'y que SELinux permita la conexión saliente (httpd_can_network_connect_db).'
            );
        }
    }

    /**
     * Obtiene la instancia única de Database (Singleton).
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna el objeto mysqli para ejecutar consultas.
     */
    public function getConnection(): mysqli
    {
        return $this->connection;
    }

    /**
     * Cierra la conexión al destruirse la instancia.
     */
    public function __destruct()
    {
        if (isset($this->connection) && $this->connection->ping()) {
            $this->connection->close();
        }
    }

    // Prevenir clonación y deserialización del Singleton
    private function __clone() {}
    public function __wakeup()
    {
        throw new RuntimeException('No se permite la deserialización del Singleton Database.');
    }
}
