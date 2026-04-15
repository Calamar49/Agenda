<?php
/**
 * ============================================================================
 * Modelo: Contacto
 * ============================================================================
 *
 * Encapsula todas las operaciones CRUD sobre la tabla `contactos` en el
 * servidor remoto MariaDB (192.168.56.105). Usa sentencias preparadas
 * (prepared statements) para prevenir inyección SQL.
 *
 * @package    AgendaWeb\Models
 * @compatible PHP 8.4.x
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/conexion.php';

class Contacto
{
    private mysqli $db;

    /** Campos permitidos para inserción/actualización */
    private const FILLABLE = [
        'nombres',
        'genero',
        'fecha_nacimiento',
        'telefono',
        'email',
        'linkedin',
        'tipo_sangre',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── READ: Obtener todos los contactos ──────────────────────────────

    /**
     * Retorna todos los contactos ordenados por fecha de creación descendente.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        $sql    = 'SELECT * FROM contactos ORDER BY created_at DESC';
        $result = $this->db->query($sql);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // ── READ: Obtener un contacto por ID ───────────────────────────────

    /**
     * Busca un contacto por su ID.
     *
     * @param  int $id
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM contactos WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    // ── CREATE: Insertar nuevo contacto ────────────────────────────────

    /**
     * Inserta un contacto nuevo en la base de datos.
     *
     * @param  array<string, mixed> $data Datos del contacto.
     * @return int ID del registro insertado.
     * @throws InvalidArgumentException Si faltan campos obligatorios.
     */
    public function create(array $data): int
    {
        $this->validateRequired($data);
        $sanitized = $this->sanitize($data);

        $sql = 'INSERT INTO contactos (nombres, genero, fecha_nacimiento, telefono, email, linkedin, tipo_sangre)
                VALUES (?, ?, ?, ?, ?, ?, ?)';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'sssssss',
            $sanitized['nombres'],
            $sanitized['genero'],
            $sanitized['fecha_nacimiento'],
            $sanitized['telefono'],
            $sanitized['email'],
            $sanitized['linkedin'],
            $sanitized['tipo_sangre']
        );
        $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();

        return $insertId;
    }

    // ── UPDATE: Actualizar contacto existente ──────────────────────────

    /**
     * Actualiza un contacto existente.
     *
     * @param  int                  $id   ID del contacto.
     * @param  array<string, mixed> $data Datos actualizados.
     * @return bool True si se actualizó al menos una fila.
     */
    public function update(int $id, array $data): bool
    {
        $this->validateRequired($data);
        $sanitized = $this->sanitize($data);

        $sql = 'UPDATE contactos
                SET nombres = ?, genero = ?, fecha_nacimiento = ?, telefono = ?,
                    email = ?, linkedin = ?, tipo_sangre = ?
                WHERE id = ?';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'sssssssi',
            $sanitized['nombres'],
            $sanitized['genero'],
            $sanitized['fecha_nacimiento'],
            $sanitized['telefono'],
            $sanitized['email'],
            $sanitized['linkedin'],
            $sanitized['tipo_sangre'],
            $id
        );
        $stmt->execute();
        $affected = $stmt->affected_rows > 0;
        $stmt->close();

        return $affected;
    }

    // ── DELETE: Eliminar contacto ──────────────────────────────────────

    /**
     * Elimina un contacto por su ID.
     *
     * @param  int  $id
     * @return bool True si se eliminó.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM contactos WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows > 0;
        $stmt->close();

        return $affected;
    }

    // ── Helpers privados ───────────────────────────────────────────────

    /**
     * Valida que los campos obligatorios estén presentes.
     *
     * @throws InvalidArgumentException
     */
    private function validateRequired(array $data): void
    {
        $required = ['nombres', 'genero', 'fecha_nacimiento', 'telefono', 'email', 'tipo_sangre'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("El campo '{$field}' es obligatorio.");
            }
        }

        // Validar formato de email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El formato del email no es válido.');
        }

        // Validar género
        $validGenders = ['Masculino', 'Femenino', 'Otro'];
        if (!in_array($data['genero'], $validGenders, true)) {
            throw new InvalidArgumentException('El género seleccionado no es válido.');
        }

        // Validar tipo de sangre
        $validBlood = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        if (!in_array($data['tipo_sangre'], $validBlood, true)) {
            throw new InvalidArgumentException('El tipo de sangre seleccionado no es válido.');
        }
    }

    /**
     * Sanitiza los datos de entrada.
     *
     * @return array<string, string|null>
     */
    private function sanitize(array $data): array
    {
        return [
            'nombres'          => trim(htmlspecialchars($data['nombres'], ENT_QUOTES, 'UTF-8')),
            'genero'           => $data['genero'],
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'telefono'         => trim(htmlspecialchars($data['telefono'], ENT_QUOTES, 'UTF-8')),
            'email'            => trim(filter_var($data['email'], FILTER_SANITIZE_EMAIL)),
            'linkedin'         => !empty($data['linkedin'])
                                    ? trim(filter_var($data['linkedin'], FILTER_SANITIZE_URL))
                                    : null,
            'tipo_sangre'      => $data['tipo_sangre'],
        ];
    }
}
