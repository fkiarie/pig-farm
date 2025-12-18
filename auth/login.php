<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Pig Farm Login</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
  <div class="row justify-content-center mt-5">
    <div class="col-md-4 col-12">

      <div class="card shadow-sm">
        <div class="card-body">

          <h4 class="text-center mb-3">Farm Login</h4>

          <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger">
              <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="process_login.php">
            <div class="mb-3">
              <label>Email</label>
              <input type="email" class="form-control" name="email" required>
            </div>

            <div class="mb-3">
              <label>Password</label>
              <input type="password" class="form-control" name="password" required>
            </div>

            <button class="btn btn-success w-100">Login</button>
          </form>

        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>
