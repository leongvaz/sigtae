<?php
/**
 * Barra de usuario + Cerrar sesión.
 * Incluir después de comprobarSesion; usa $_SESSION['nombre'] y $_SESSION['zona'].
 */
if (!isset($_SESSION['user'])) {
  return;
}
$nombre = $_SESSION['nombre'] ?? '';
$zona   = $_SESSION['zona']   ?? '';
?>
<div class="d-flex align-items-center gap-2 flex-wrap">
  <span class="text-muted small">
    <span class="fw-medium text-body"><?php echo htmlspecialchars($nombre); ?></span>
    <?php if ($zona !== ''): ?>
      <span class="text-secondary">·</span>
      <span><?php echo htmlspecialchars($zona); ?></span>
    <?php endif; ?>
  </span>
  <a href="Login/cerrarSession.php" class="btn btn-outline-danger btn-sm" title="Cerrar sesión">
    Cerrar sesión
  </a>
</div>
