<?php
/**
 * Sidebar include para Profesor (Teacher)
 * Solo muestra: Inicio (dashboard), Pasar Lista, Ver Alumnos
 * NO puede añadir/editar/eliminar alumnos ni gestionar grupos
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
        <p class="subtitle">Panel del Profesor</p>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/teacher/index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>">
            <span class="nav-icon">🏠</span> Inicio
        </a>
        <a href="<?= BASE_URL ?>/teacher/attendance.php" class="<?= $currentPage === 'attendance' ? 'active' : '' ?>">
            <span class="nav-icon">✅</span> Pasar Lista
        </a>
        <a href="<?= BASE_URL ?>/teacher/students.php" class="<?= $currentPage === 'students' ? 'active' : '' ?>">
            <span class="nav-icon">👦</span> Mis Alumnos
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr(Auth::getUserName() ?? 'P', 0, 1)) ?></div>
            <div>
                <div style="font-weight: 500; color: var(--text);"><?= htmlspecialchars(Auth::getUserName() ?? '') ?></div>
                <div style="font-size: 0.75rem;">Profesor</div>
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
