<?php
/**
 * ============================================================================
 * AGENDA WEB DINÁMICA - Punto de entrada principal (Front Controller)
 * ============================================================================
 *
 * Arquitectura de Red (Fedora Server 43):
 *   Nodo 1 - Servidor Web:  192.168.56.104  (Apache + PHP 8.4 + HTTPS)
 *   Nodo 2 - Servidor DB:   192.168.56.105  (MariaDB)
 *
 * @package    AgendaWeb
 * @compatible PHP 8.4.x
 */

declare(strict_types=1);

// Iniciar sesión para mensajes flash (PRG pattern)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/Controllers/ContactoController.php';

// ── Front Controller: despachar acción ─────────────────────────────────────
$controller = new ContactoController();
$action     = $_GET['action'] ?? 'index';

// Las acciones que retornan JSON (AJAX) salen antes de renderizar HTML
if ($action === 'edit') {
    $controller->dispatch();
    exit;
}

// Las acciones POST redirigen (PRG), no renderizan vista
if (in_array($action, ['store', 'update', 'delete'], true)) {
    $controller->dispatch();
    exit;
}

// Para 'index': obtener datos para la vista
$viewData  = $controller->index();
$contactos = $viewData['contactos'];
$flash     = $viewData['flash'];

// ── Helper: calcular edad ───────────────────────────────────────────────────
function calcularEdad(string $fechaNac): int
{
    return (int) (new DateTime($fechaNac))->diff(new DateTime())->y;
}

// ── Helper: badge de tipo de sangre ────────────────────────────────────────
function badgeClass(string $tipo): string
{
    return match (true) {
        str_starts_with($tipo, 'AB') => 'badge-AB',
        str_starts_with($tipo, 'A')  => 'badge-A',
        str_starts_with($tipo, 'B')  => 'badge-B',
        default                      => 'badge-O',
    };
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Agenda Web Dinámica – Sistema CRUD de contactos para laboratorio de servidores de red en Fedora Server 43">
    <title>Agenda Web | Laboratorio Fedora</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons (CDN) para íconos ligeros -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Hoja de estilos principal -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">

    <!-- ══ HEADER ══════════════════════════════════════════════════════════ -->
    <header>
        <div>
            <h1><i class="bi bi-person-lines-fill"></i> Agenda Web</h1>
            <p style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.25rem;">
                <i class="bi bi-hdd-network"></i> Nodo Web: 192.168.56.104 &nbsp;→&nbsp;
                <i class="bi bi-database"></i> Nodo DB: 192.168.56.105
            </p>
        </div>
        <button id="btn-nuevo" class="btn btn-primary" onclick="openModal('modal-crear')">
            <i class="bi bi-person-plus-fill"></i> Nuevo Contacto
        </button>
    </header>

    <!-- ══ ALERT FLASH ══════════════════════════════════════════════════════ -->
    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- ══ TABLA DE CONTACTOS ══════════════════════════════════════════════ -->
    <section class="glass-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.2rem; font-weight: 600;">
                <i class="bi bi-table"></i> Contactos Registrados
                <span style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 400;">
                    (<?= count($contactos) ?> registros)
                </span>
            </h2>
            <input type="text" id="buscador" class="form-control"
                   placeholder="🔍 Buscar contacto..." style="max-width: 260px; padding: 0.5rem 1rem;">
        </div>

        <div class="table-container">
            <?php if (empty($contactos)): ?>
                <div style="text-align: center; padding: 3rem 0; color: var(--text-secondary);">
                    <i class="bi bi-inbox" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                    No hay contactos registrados aún.<br>
                    <small>Haz clic en <strong>Nuevo Contacto</strong> para agregar el primero.</small>
                </div>
            <?php else: ?>
                <table id="tabla-contactos">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombres</th>
                            <th>Género</th>
                            <th>Edad</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>LinkedIn</th>
                            <th>Sangre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contactos as $c): ?>
                        <tr>
                            <td style="color: var(--text-secondary);"><?= (int) $c['id'] ?></td>
                            <td style="font-weight: 600;">
                                <?= htmlspecialchars($c['nombres'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><?= htmlspecialchars($c['genero'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= calcularEdad($c['fecha_nacimiento']) ?> años</td>
                            <td><?= htmlspecialchars($c['telefono'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?>"
                                   style="color: var(--primary);">
                                    <?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td>
                                <?php if (!empty($c['linkedin'])): ?>
                                    <a href="<?= htmlspecialchars($c['linkedin'], ENT_QUOTES, 'UTF-8') ?>"
                                       target="_blank" rel="noopener noreferrer"
                                       style="color: #0A66C2;">
                                        <i class="bi bi-linkedin"></i> Ver perfil
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= badgeClass($c['tipo_sangre']) ?>">
                                    <?= htmlspecialchars($c['tipo_sangre'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-edit"
                                            onclick="openEditModal(<?= (int) $c['id'] ?>)"
                                            title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn btn-danger"
                                            onclick="confirmDelete(<?= (int) $c['id'] ?>, '<?= htmlspecialchars($c['nombres'], ENT_QUOTES | ENT_JS, 'UTF-8') ?>')"
                                            title="Eliminar">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

</div><!-- /container -->


<!-- ══ MODAL: CREAR CONTACTO ════════════════════════════════════════════════ -->
<div id="modal-crear" class="modal" role="dialog" aria-modal="true" aria-labelledby="title-crear">
    <div class="modal-content glass-panel">
        <div class="modal-header">
            <h3 id="title-crear" style="font-size: 1.1rem;">
                <i class="bi bi-person-plus-fill" style="color: var(--primary);"></i>
                Nuevo Contacto
            </h3>
            <button class="close-btn" onclick="closeModal('modal-crear')" aria-label="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form action="index.php?action=store" method="POST" id="form-crear" novalidate>
            <div class="form-grid">
                <!-- Nombres -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="c-nombres">Nombres completos *</label>
                    <input type="text" id="c-nombres" name="nombres" class="form-control"
                           placeholder="Ej. Juan Carlos Pérez" required maxlength="150">
                </div>

                <!-- Género -->
                <div class="form-group">
                    <label for="c-genero">Género *</label>
                    <select id="c-genero" name="genero" class="form-control" required>
                        <option value="" disabled selected>Seleccionar…</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <!-- Fecha de nacimiento -->
                <div class="form-group">
                    <label for="c-fecha">Fecha de Nacimiento *</label>
                    <input type="date" id="c-fecha" name="fecha_nacimiento" class="form-control"
                           required max="<?= date('Y-m-d') ?>">
                </div>

                <!-- Teléfono -->
                <div class="form-group">
                    <label for="c-telefono">Teléfono *</label>
                    <input type="tel" id="c-telefono" name="telefono" class="form-control"
                           placeholder="+52 555 123 4567" required maxlength="20">
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="c-email">Correo Electrónico *</label>
                    <input type="email" id="c-email" name="email" class="form-control"
                           placeholder="correo@ejemplo.com" required maxlength="150">
                </div>

                <!-- LinkedIn -->
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="c-linkedin">LinkedIn (opcional)</label>
                    <input type="url" id="c-linkedin" name="linkedin" class="form-control"
                           placeholder="https://linkedin.com/in/usuario" maxlength="255">
                </div>

                <!-- Tipo de sangre -->
                <div class="form-group">
                    <label for="c-sangre">Tipo de Sangre *</label>
                    <select id="c-sangre" name="tipo_sangre" class="form-control" required>
                        <option value="" disabled selected>Seleccionar…</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn" style="color: var(--text-secondary);"
                        onclick="closeModal('modal-crear')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save2-fill"></i> Guardar Contacto
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ MODAL: EDITAR CONTACTO ═══════════════════════════════════════════════ -->
<div id="modal-editar" class="modal" role="dialog" aria-modal="true" aria-labelledby="title-editar">
    <div class="modal-content glass-panel">
        <div class="modal-header">
            <h3 id="title-editar" style="font-size: 1.1rem;">
                <i class="bi bi-pencil-square" style="color: var(--secondary);"></i>
                Editar Contacto
            </h3>
            <button class="close-btn" onclick="closeModal('modal-editar')" aria-label="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form action="index.php?action=update" method="POST" id="form-editar" novalidate>
            <input type="hidden" id="e-id" name="id">
            <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="e-nombres">Nombres completos *</label>
                    <input type="text" id="e-nombres" name="nombres" class="form-control" required maxlength="150">
                </div>

                <div class="form-group">
                    <label for="e-genero">Género *</label>
                    <select id="e-genero" name="genero" class="form-control" required>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="e-fecha">Fecha de Nacimiento *</label>
                    <input type="date" id="e-fecha" name="fecha_nacimiento" class="form-control"
                           required max="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label for="e-telefono">Teléfono *</label>
                    <input type="tel" id="e-telefono" name="telefono" class="form-control" required maxlength="20">
                </div>

                <div class="form-group">
                    <label for="e-email">Correo Electrónico *</label>
                    <input type="email" id="e-email" name="email" class="form-control" required maxlength="150">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="e-linkedin">LinkedIn (opcional)</label>
                    <input type="url" id="e-linkedin" name="linkedin" class="form-control" maxlength="255">
                </div>

                <div class="form-group">
                    <label for="e-sangre">Tipo de Sangre *</label>
                    <select id="e-sangre" name="tipo_sangre" class="form-control" required>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn" style="color: var(--text-secondary);"
                        onclick="closeModal('modal-editar')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save2-fill"></i> Actualizar
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ MODAL: CONFIRMAR ELIMINACIÓN ════════════════════════════════════════ -->
<div id="modal-eliminar" class="modal" role="dialog" aria-modal="true">
    <div class="modal-content glass-panel" style="max-width: 420px; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
        <h3 style="margin-bottom: 0.5rem;">¿Eliminar contacto?</h3>
        <p id="msg-eliminar" style="color: var(--text-secondary); margin-bottom: 1.5rem;"></p>
        <div style="display: flex; gap: 0.75rem; justify-content: center;">
            <button class="btn" style="color: var(--text-secondary);"
                    onclick="closeModal('modal-eliminar')">Cancelar</button>
            <a id="link-eliminar" href="#" class="btn btn-danger">
                <i class="bi bi-trash-fill"></i> Sí, eliminar
            </a>
        </div>
    </div>
</div>


<!-- ══ JAVASCRIPT ════════════════════════════════════════════════════════════ -->
<script>
/**
 * Gestión de modales accesibles.
 */
function openModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    // Focus al primer input del modal para accesibilidad
    setTimeout(() => {
        const first = modal.querySelector('input, select, button:not(.close-btn)');
        if (first) first.focus();
    }, 100);
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

// Cerrar modal al hacer clic en el backdrop
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function (e) {
        if (e.target === this) closeModal(this.id);
    });
});

// Cerrar modal con la tecla Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active')
                .forEach(m => closeModal(m.id));
    }
});

/**
 * Carga los datos del contacto vía fetch y abre el modal de edición.
 * @param {number} id - ID del contacto a editar.
 */
function openEditModal(id) {
    fetch(`index.php?action=edit&id=${id}`)
        .then(res => {
            if (!res.ok) throw new Error('Error al obtener el contacto.');
            return res.json();
        })
        .then(data => {
            if (!data || !data.id) throw new Error('Contacto no encontrado.');

            document.getElementById('e-id').value            = data.id;
            document.getElementById('e-nombres').value       = data.nombres;
            document.getElementById('e-genero').value        = data.genero;
            document.getElementById('e-fecha').value         = data.fecha_nacimiento;
            document.getElementById('e-telefono').value      = data.telefono;
            document.getElementById('e-email').value         = data.email;
            document.getElementById('e-linkedin').value      = data.linkedin ?? '';
            document.getElementById('e-sangre').value        = data.tipo_sangre;

            openModal('modal-editar');
        })
        .catch(err => alert('❌ ' + err.message));
}

/**
 * Abre el modal de confirmación de eliminación.
 * @param {number} id     - ID del contacto.
 * @param {string} nombre - Nombre del contacto para el mensaje.
 */
function confirmDelete(id, nombre) {
    document.getElementById('msg-eliminar').textContent =
        `Estás a punto de eliminar a "${nombre}". Esta acción no se puede deshacer.`;
    document.getElementById('link-eliminar').href = `index.php?action=delete&id=${id}`;
    openModal('modal-eliminar');
}

/**
 * Filtro en tiempo real sobre la tabla de contactos.
 */
document.getElementById('buscador').addEventListener('input', function () {
    const filter = this.value.toLowerCase().trim();
    document.querySelectorAll('#tabla-contactos tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
