<?php
/**
 * ============================================================================
 * Controlador: ContactoController
 * ============================================================================
 *
 * Maneja las acciones HTTP (GET/POST) y delega la lógica de negocio
 * al modelo Contacto. Aplica el patrón PRG (Post/Redirect/Get) para
 * evitar reenvíos de formulario accidentales.
 *
 * @package    AgendaWeb\Controllers
 * @compatible PHP 8.4.x
 */

declare(strict_types=1);

require_once __DIR__ . '/../Models/Contacto.php';

class ContactoController
{
    private Contacto $model;

    public function __construct()
    {
        $this->model = new Contacto();
    }

    /**
     * Despacha la acción según el parámetro 'action' de la petición.
     */
    public function dispatch(): void
    {
        $action = $_GET['action'] ?? 'index';

        match ($action) {
            'store'  => $this->store(),
            'edit'   => $this->edit(),
            'update' => $this->update(),
            'delete' => $this->delete(),
            default  => $this->index(),
        };
    }

    // ── READ ───────────────────────────────────────────────────────────

    /**
     * Retorna todos los contactos para la vista.
     *
     * @return array{contactos: array, flash: array}
     */
    public function index(): array
    {
        $flash = $this->getFlash();

        try {
            $contactos = $this->model->getAll();
        } catch (RuntimeException $e) {
            $contactos = [];
            $flash     = ['type' => 'error', 'message' => $e->getMessage()];
        }

        return compact('contactos', 'flash');
    }

    // ── READ (single) ──────────────────────────────────────────────────

    /**
     * Retorna datos de un contacto específico (para el modal de edición).
     */
    public function edit(): void
    {
        $id      = (int) ($_GET['id'] ?? 0);
        $contact = $this->model->getById($id);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($contact ?? []);
        exit;
    }

    // ── CREATE ─────────────────────────────────────────────────────────

    /**
     * Procesa el formulario de creación.
     * Patrón PRG: redirige tras el POST para evitar reenvíos.
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index');
            return;
        }

        try {
            $this->model->create($_POST);
            $this->setFlash('success', '✅ Contacto registrado exitosamente.');
        } catch (InvalidArgumentException $e) {
            $this->setFlash('error', '❌ ' . $e->getMessage());
        } catch (RuntimeException $e) {
            $this->setFlash('error', '❌ Error de base de datos: ' . $e->getMessage());
        }

        $this->redirect('index');
    }

    // ── UPDATE ─────────────────────────────────────────────────────────

    /**
     * Procesa el formulario de actualización.
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index');
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);

        try {
            $updated = $this->model->update($id, $_POST);
            $msg     = $updated
                ? '✅ Contacto actualizado correctamente.'
                : '⚠️ No se encontraron cambios para actualizar.';
            $this->setFlash('success', $msg);
        } catch (InvalidArgumentException $e) {
            $this->setFlash('error', '❌ ' . $e->getMessage());
        } catch (RuntimeException $e) {
            $this->setFlash('error', '❌ Error de base de datos: ' . $e->getMessage());
        }

        $this->redirect('index');
    }

    // ── DELETE ─────────────────────────────────────────────────────────

    /**
     * Elimina un contacto por ID.
     */
    public function delete(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        try {
            $deleted = $this->model->delete($id);
            $msg     = $deleted
                ? '✅ Contacto eliminado correctamente.'
                : '⚠️ No se encontró el contacto con ese ID.';
            $this->setFlash($deleted ? 'success' : 'error', $msg);
        } catch (RuntimeException $e) {
            $this->setFlash('error', '❌ Error de base de datos: ' . $e->getMessage());
        }

        $this->redirect('index');
    }

    // ── Helpers privados ───────────────────────────────────────────────

    /** Redirige a la acción dada usando el patrón PRG. */
    private function redirect(string $action): void
    {
        header("Location: index.php?action={$action}");
        exit;
    }

    /** Almacena un mensaje flash en sesión. */
    private function setFlash(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'] = compact('type', 'message');
    }

    /** Recupera y elimina el mensaje flash de la sesión. */
    private function getFlash(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
}
