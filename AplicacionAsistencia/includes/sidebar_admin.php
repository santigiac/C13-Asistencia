<?php
/**
 * Sidebar include para Admin
 * Incluir en todas las páginas admin
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Mobile Header -->
<header class="mobile-header">
    <button class="hamburger" onclick="toggleSidebar()" aria-label="Menú">☰</button>
    <h2>Cultura Tretze</h2>
    <div></div>
</header>

<!-- Overlay para cerrar sidebar en móvil -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>📋 Cultura Tretze</h2>
        <p class="subtitle">Sistema de Asistencia</p>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">🏠</span> Inicio
        </a>
        <a href="<?= BASE_URL ?>/admin/attendance.php" class="<?= $currentPage === 'attendance' ? 'active' : '' ?>">
            <span class="nav-icon">✅</span> Pasar Lista
        </a>
        <a href="<?= BASE_URL ?>/admin/students.php" class="<?= $currentPage === 'students' ? 'active' : '' ?>">
            <span class="nav-icon">👦</span> Alumnos
        </a>
        <a href="<?= BASE_URL ?>/admin/groups.php" class="<?= $currentPage === 'groups' ? 'active' : '' ?>">
            <span class="nav-icon">👥</span> Grupos
        </a>
        <a href="<?= BASE_URL ?>/admin/parents.php" class="<?= $currentPage === 'parents' ? 'active' : '' ?>">
            <span class="nav-icon">👨‍👩‍👧</span> Padres
        </a>
        <a href="<?= BASE_URL ?>/admin/export.php" class="<?= $currentPage === 'export' ? 'active' : '' ?>">
            <span class="nav-icon">📥</span> Exportar
        </a>
        <a href="<?= BASE_URL ?>/admin/import.php" class="<?= $currentPage === 'import' ? 'active' : '' ?>">
            <span class="nav-icon">📤</span> Importar Excel
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr(Auth::getUserName() ?? 'A', 0, 1)) ?></div>
            <div>
                <div style="font-weight: 500; color: var(--text);"><?= htmlspecialchars(Auth::getUserName() ?? '') ?></div>
                <div style="font-size: 0.75rem;">Administrador</div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline btn-sm btn-block mt-2">
            Cerrar Sesión
        </a>
    </div>
</aside>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
</script>
