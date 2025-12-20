<nav class="navbar navbar-dark bg-success fixed-top">
  <div class="container-fluid">

    <button class="navbar-toggler d-md-none" type="button"
      data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <span class="navbar-brand mb-0 h1">
      🐖 Pig Farm
    </span>

    <div class="dropdown">
      <a class="text-white dropdown-toggle" href="#" role="button"
         data-bs-toggle="dropdown">
        <?= htmlspecialchars($_SESSION['user_name']); ?>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="#">Profile</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="/auth/logout.php">Logout</a></li>
      </ul>
    </div>

  </div>
</nav>
