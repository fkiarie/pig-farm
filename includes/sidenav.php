<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function isActive($path)
{
    global $currentPath;
    return str_contains($currentPath, $path) ? 'active' : '';
}
?>

<nav id="sidebarMenu"
     class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">

  <div class="position-sticky pt-3">

    <ul class="nav flex-column">

      <!-- Dashboard -->
      <li class="nav-item">
        <a class="nav-link <?= isActive('/index.php') ?>" href="<?= BASE_URL ?>/index.php">
          <i class="bi bi-speedometer2 me-2"></i>
          Dashboard
        </a>
      </li>

      <!-- Sows -->
      <li class="nav-item">
        <a class="nav-link <?= isActive('/sows/') ?>" href="<?= BASE_URL ?>/sows/list.php">
          <i class="bi bi-gender-female me-2"></i>
          Sows
        </a>
      </li>

      <!-- Boars -->
      <li class="nav-item">
        <a class="nav-link <?= isActive('/boars/') ?>" href="<?= BASE_URL ?>/boars/list.php">
          <i class="bi bi-gender-male me-2"></i>
          Boars
        </a>
      </li>

      <!-- Serving -->
      <li class="nav-item">
        <a class="nav-link <?= isActive('/serving/') ?>" href="<?= BASE_URL ?>/serving/list.php">
          <i class="bi bi-heart-pulse me-2"></i>
          Serving
        </a>
      </li>

      <!-- Farrowing -->
      <li class="nav-item">
        <a class="nav-link <?= isActive('/farrowing/') ?>" href="<?= BASE_URL ?>/farrowing/list.php">
          <i class="bi bi-egg-fried me-2"></i>
          Farrowing
        </a>
      </li>

      <!-- Weaning -->
      <li class="nav-item">
        <a class="nav-link <?= isActive('/weaning/') ?>" href="<?= BASE_URL ?>/weaning/list.php">
          <i class="bi bi-cup-straw me-2"></i>
          Weaning
        </a>
      </li>

      <!-- Daily Activities -->
      <li class="nav-item">
        <a class="nav-link <?= isActive('/activities/') ?>" href="<?= BASE_URL ?>/activities/list.php">
          <i class="bi bi-journal-text me-2"></i>
          Daily Activities
        </a>
      </li>

    </ul>

  </div>
</nav>
